<?php

namespace App\Filament\Pages;

use App\Enums\CaptureSessionStatus;
use App\Enums\MetadataRevisionType;
use App\Filament\Resources\CaptureSessions\CaptureSessionResource;
use App\Jobs\PersistCaptureSessionJob;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Services\BookCoverOcrService;
use App\Services\OllamaService;
use BackedEnum;
use Exception;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class CaptureBook extends Page
{
    protected string $view = 'filament.pages.capture-book';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Intake';

    protected static ?int $navigationSort = 5;

    public static ?string $title = 'Capture New Book';

    protected static ?string $navigationLabel = 'Capture New Book';

    public ?string $frontImageData = null;

    public ?int $frontImageWidth = null;

    public ?int $frontImageHeight = null;

    public ?string $isbnBarcodeValue = null;

    public ?string $frontOcrTitle = null;

    public ?string $frontOcrSubtitle = null;

    public ?string $frontOcrAuthors = null;

    public ?string $frontOcrPublisher = null;

    public ?string $frontOcrText = null;

    public ?string $frontOcrConfidence = null;

    public int $quantity = 1;

    public ?string $bookTitle = null;

    public ?string $bookAuthors = null;

    public array $ocrTokens = [];

    public bool $isExtracting = false;

    public ?string $lastScannedIsbn = null;

    public function scannedIsbn(string $isbn): void
    {
        $this->lastScannedIsbn = $isbn;

        Notification::make()
            ->title('ISBN Scanned')
            ->body("Found: {$isbn}")
            ->success()
            ->send();
    }

    public function resetCapture(): void
    {
        $this->frontImageData = null;
        $this->frontImageWidth = null;
        $this->frontImageHeight = null;
        $this->bookTitle = null;
        $this->bookAuthors = null;
        $this->ocrTokens = [];

        $this->dispatch('capture-reset');
    }

    public function updatedQuantity($value): void
    {
        if (! is_numeric($value) || $value < 1) {
            $this->quantity = 1;
        }
    }

    public function extractTitleFromFrontImage(OllamaService $ollama, bool $silent = true): void
    {
        if (! $this->frontImageData) {
            return;
        }

        $this->isExtracting = true;

        try {
            // Save temporary image for Ollama
            $tempPath = 'temp/'.uniqid().'.jpg';
            $data = explode(',', $this->frontImageData)[1];
            Storage::disk('public')->put($tempPath, base64_decode($data));

            $prompt = "Read this Indonesian book cover with OCR. Extract ALL visible text. Do not interpret or structure it. Break the text into individual words/tokens separated by a single space. Respond ONLY with a JSON object containing a 'text' field.";

            $response = $ollama->extractFromImage($tempPath, $prompt);
            $result = json_decode($response['response'] ?? '{}', true);
            $rawText = $result['text'] ?? '';

            if ($rawText) {
                $newTokens = array_values(array_filter(preg_split('/\s+/', $rawText)));
                // Only update if we found something meaningful and tokens changed significantly
                if (count($newTokens) > 0 && implode(' ', $newTokens) !== implode(' ', $this->ocrTokens)) {
                    $this->ocrTokens = $newTokens;
                    $this->dispatch('tokens-extracted', tokens: $this->ocrTokens);
                }
            }

            Storage::disk('public')->delete($tempPath);

        } catch (Exception $e) {
            Log::error('Manual extraction failed: '.$e->getMessage());
            if (! $silent) {
                Notification::make()
                    ->title('Text extraction failed')
                    ->danger()
                    ->send();
            }
        } finally {
            $this->isExtracting = false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'frontImageData' => ['required', 'string'],
            'frontImageWidth' => ['nullable', 'integer', 'min:1'],
            'frontImageHeight' => ['nullable', 'integer', 'min:1'],
            'isbnBarcodeValue' => ['nullable', 'string', 'max:64', 'regex:/^[0-9]+$/'],
            'frontOcrTitle' => ['nullable', 'string', 'max:255'],
            'frontOcrSubtitle' => ['nullable', 'string', 'max:1000'],
            'frontOcrAuthors' => ['nullable', 'string', 'max:1000'],
            'frontOcrPublisher' => ['nullable', 'string', 'max:255'],
            'frontOcrText' => ['nullable', 'string', 'max:5000'],
            'frontOcrConfidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'bookTitle' => ['nullable', 'string', 'max:255'],
            'bookAuthors' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array{ok: bool, metadata?: array<string, mixed>, error?: string}
     */
    public function previewFrontOcr(string $imageData): array
    {
        try {
            $metadata = app(BookCoverOcrService::class)->extractFromDataUrl($imageData);

            return [
                'ok' => true,
                'metadata' => [
                    'title' => $metadata['title'] ?? null,
                    'subtitle' => $metadata['subtitle'] ?? null,
                    'authors' => $metadata['authors'] ?? [],
                    'publisher' => $metadata['publisher'] ?? null,
                    'confidence' => $metadata['confidence'] ?? null,
                    'ocr_text' => $metadata['ocr_text'] ?? null,
                ],
            ];
        } catch (Exception $exception) {
            report($exception);

            return [
                'ok' => false,
                'error' => 'OCR preview could not read this frame.',
            ];
        }
    }

    public function submit(): void
    {
        $this->validate();

        $captureSession = DB::transaction(function (): CaptureSession {
            $captureSession = CaptureSession::create([
                'submitted_by' => auth()->id(),
                'status' => CaptureSessionStatus::Captured,
                'quantity' => $this->quantity,
                'submitted_at' => now(),
            ]);

            $frontImage = $this->storeCapturedImage($this->frontImageData, $captureSession->public_id, 'front');

            $captureSession->update([
                'front_image_path' => $frontImage['path'],
                'front_image_meta' => [
                    'mime_type' => $frontImage['mime_type'],
                    'size_bytes' => $frontImage['size_bytes'],
                    'width' => $this->frontImageWidth,
                    'height' => $this->frontImageHeight,
                ],
                'back_image_meta' => [
                    'barcode_value' => $this->isbnBarcodeValue,
                    'barcode_type' => '1d',
                ],
            ]);

            MetadataRevision::create([
                'capture_session_id' => $captureSession->id,
                'revision_type' => MetadataRevisionType::RawCapture,
                'source_stage' => 'capture_page',
                'source_actor_type' => auth()->user()::class,
                'source_actor_id' => auth()->id(),
                'payload' => [
                    'title' => $this->bookTitle,
                    'authors' => $this->bookAuthors ? array_values(array_filter(array_map('trim', explode(',', $this->bookAuthors)))) : [],
                    'isbn' => $this->lastScannedIsbn,
                    'front_image_path' => $frontImage['path'],
                    'isbn_barcode_value' => $this->isbnBarcodeValue,
                    'quantity' => $this->quantity,
                    'notes' => 'Raw browser camera capture submitted for review.',
                ],
                'source_meta' => [
                    'capture_page_version' => 'browser_auto_capture_v1',
                    'front_image_meta' => $captureSession->front_image_meta,
                    'back_image_meta' => $captureSession->back_image_meta,
                ],
            ]);

            if (filled($this->frontOcrTitle)) {
                MetadataRevision::create([
                    'capture_session_id' => $captureSession->id,
                    'revision_type' => MetadataRevisionType::LlmDraft,
                    'source_stage' => 'vision_extraction',
                    'source_actor_type' => auth()->user()::class,
                    'source_actor_id' => auth()->id(),
                    'confidence_score' => $this->frontOcrConfidence,
                    'payload' => [
                        'title' => $this->frontOcrTitle,
                        'subtitle' => $this->frontOcrSubtitle,
                        'authors' => $this->frontOcrAuthors ? array_values(array_filter(array_map('trim', explode(',', $this->frontOcrAuthors)))) : [],
                        'publisher' => $this->frontOcrPublisher,
                        'ocr_text' => $this->frontOcrText,
                        'front_image_path' => $frontImage['path'],
                        'notes' => 'Accepted realtime OCR preview during capture.',
                    ],
                    'source_meta' => [
                        'model' => config('services.ollama.vision_model', 'glm-ocr'),
                        'capture_page_version' => 'browser_realtime_ocr_v1',
                    ],
                ]);
            }

            return $captureSession;
        });

        Notification::make()
            ->title('Capture session saved')
            ->body("Session {$captureSession->public_id} is being processed.")
            ->success()
            ->send();

        $captureSession->update(['status' => CaptureSessionStatus::Processing]);

        // Directly persist since title and ISBN are confirmed in the UI
        PersistCaptureSessionJob::dispatch($captureSession);

        $this->redirect(CaptureSessionResource::getUrl('view', ['record' => $captureSession]).'?autoback=1');
    }

    /**
     * @return array{path: string, mime_type: string, size_bytes: int}
     */
    private function storeCapturedImage(?string $dataUrl, string $publicId, string $side): array
    {
        if (! is_string($dataUrl) || ! preg_match('/^data:(image\/(?:jpeg|png|webp));base64,(.+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                "{$side}ImageData" => 'The captured image payload is invalid.',
            ]);
        }

        $bytes = base64_decode($matches[2], strict: true);

        if ($bytes === false) {
            throw ValidationException::withMessages([
                "{$side}ImageData" => 'The captured image could not be decoded.',
            ]);
        }

        $extension = match ($matches[1]) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = "capture-sessions/{$publicId}/{$side}.{$extension}";

        Storage::disk('public')->put($path, $bytes, 'public');

        return [
            'path' => $path,
            'mime_type' => $matches[1],
            'size_bytes' => strlen($bytes),
        ];
    }
}
