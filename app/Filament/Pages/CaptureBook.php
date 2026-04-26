<?php

namespace App\Filament\Pages;

use App\Enums\CaptureSessionStatus;
use App\Enums\MetadataRevisionType;
use App\Filament\Resources\CaptureSessions\CaptureSessionResource;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Services\BookCoverOcrService;
use BackedEnum;
use Exception;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class CaptureBook extends Page
{
    protected string $view = 'filament.pages.capture-book';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Intake';

    protected static ?int $navigationSort = 5;

    public static ?string $title = 'Capture Book';

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

    public function updatedQuantity($value): void
    {
        if (! is_numeric($value) || $value < 1) {
            $this->quantity = 1;
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
            'isbnBarcodeValue' => ['required', 'string', 'max:64', 'regex:/^[0-9]+$/'],
            'frontOcrTitle' => ['nullable', 'string', 'max:255'],
            'frontOcrSubtitle' => ['nullable', 'string', 'max:1000'],
            'frontOcrAuthors' => ['nullable', 'string', 'max:1000'],
            'frontOcrPublisher' => ['nullable', 'string', 'max:255'],
            'frontOcrText' => ['nullable', 'string', 'max:5000'],
            'frontOcrConfidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'quantity' => ['required', 'integer', 'min:1'],
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
            ->body("Session {$captureSession->public_id} is ready for review.")
            ->success()
            ->send();

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
