<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

class BookCoverOcrService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly OllamaService $ollama,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function extractFromStoragePath(string $imagePath): array
    {
        $startedAt = hrtime(true);
        $ocrImage = $this->optimizedOcrImage(Storage::disk('public')->get($imagePath));
        $temporaryPath = 'temp/ocr-'.Str::uuid().'.png';
        Storage::disk('public')->put($temporaryPath, $ocrImage);

        try {
            $result = $this->metadataFromResponse(
                $this->ollama->extractFromImage($temporaryPath, $this->prompt()),
            );

            Log::info('Book cover OCR completed.', [
                'source_bytes' => Storage::disk('public')->size($imagePath),
                'ocr_bytes' => strlen($ocrImage),
                'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            ]);

            return $result;
        } finally {
            Storage::disk('public')->delete($temporaryPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function extractFromDataUrl(string $dataUrl): array
    {
        return $this->previewFromDataUrl($dataUrl);
    }
    public function previewFromDataUrl(string $dataUrl): array
    {
        $startedAt = hrtime(true);
        $imageBytes = $this->decodeImageDataUrl($dataUrl);
        $ocrImage = $this->optimizedOcrImage($imageBytes);
        $response = $this->ollama->extractFromImageBytes($ocrImage, $this->prompt());
        $metadata = $this->metadataFromResponse($response);

        Log::info('Book cover OCR preview completed.', [
            'source_bytes' => strlen($imageBytes),
            'ocr_bytes' => strlen($ocrImage),
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ]);

        return $metadata;
    }

    public function prompt(): string
    {
        if (config('services.ollama.vision_model') === 'glm-ocr') {
            return 'Text Recognition:';
        }

        return <<<'PROMPT'
Please output the information in the image according to this JSON format:
{
  "title": "",
  "subtitle": "",
  "authors": [],
  "publisher": "",
  "confidence": 0
}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function metadataFromResponse(array $response): array
    {
        $rawMetadata = ($response['response'] ?? '') ?: ($response['thinking'] ?? '{}');

        if (! is_string($rawMetadata)) {
            $rawMetadata = '{}';
        }

        $rawMetadata = trim($rawMetadata);
        $rawMetadata = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $rawMetadata) ?? $rawMetadata;

        $data = json_decode(trim($rawMetadata), true, flags: JSON_THROW_ON_ERROR);

        if (isset($data['text']) && is_string($data['text']) && ! isset($data['title'])) {
            return $this->metadataFromOcrText($data['text']);
        }

        if (isset($data['authors']) && is_string($data['authors'])) {
            $data['authors'] = array_map('trim', explode('&', $data['authors']));
        }

        return $data;
    }

    /**
     * @return array{title: string|null, subtitle: string|null, authors: array<int, string>, publisher: string|null, confidence: float, ocr_text: string}
     */
    public function metadataFromOcrText(string $ocrText): array
    {
        $lines = collect(preg_split('/\R/', $ocrText) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        $titleStart = $lines->search(fn (string $line): bool => $this->isTitleLikeLine($line));
        $titleLines = $titleStart === false
            ? collect()
            : $lines->slice($titleStart)->takeWhile(fn (string $line): bool => $this->isTitleLikeLine($line))->values();
        $titleBlockCount = $titleLines->count();

        $prefix = null;
        $publisher = null;

        while ($titleLines->count() >= 2 && mb_strlen($titleLines->first()) <= 4) {
            $publisher = $titleLines->shift();
        }

        if ($titleLines->count() >= 3 && str($titleLines->first())->contains(' ') === false) {
            $prefix = $titleLines->shift();
        }

        $authors = $titleStart === false
            ? []
            : $lines->slice(0, $titleStart)
                ->flatMap(fn (string $line): array => preg_split('/\s*&\s*/', $line) ?: [])
                ->map(fn (string $author): string => trim($author))
                ->filter()
                ->values()
                ->all();

        $subtitleLines = $titleStart === false
            ? collect()
            : $lines->slice($titleStart + $titleBlockCount)->values();

        return [
            'title' => $titleLines->map(fn (string $line): string => str($line)->title()->toString())->implode(' ') ?: null,
            'subtitle' => collect([$prefix ? str($prefix)->title()->toString() : null])
                ->merge($subtitleLines)
                ->filter()
                ->implode(' ') ?: null,
            'authors' => $authors,
            'publisher' => $publisher,
            'confidence' => 0.95,
            'ocr_text' => $ocrText,
        ];
    }

    private function isTitleLikeLine(string $line): bool
    {
        return $line === mb_strtoupper($line) && preg_match('/\pL/u', $line) === 1;
    }

    private function optimizedOcrImage(string $imageBytes): string
    {
        $sourceBytes = strlen($imageBytes);
        $sourceImage = imagecreatefromstring($imageBytes);

        if ($sourceImage === false) {
            throw ValidationException::withMessages([
                'image' => 'The capture image could not be decoded for OCR.',
            ]);
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($sourceImage);

            throw ValidationException::withMessages([
                'image' => 'The capture image has invalid dimensions for OCR.',
            ]);
        }

        $maxDimension = max(512, config('services.ollama.max_image_dimension', 1600));
        $scale = min(1, $maxDimension / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        ob_start();
        imagepng($targetImage, null, 6);
        $optimizedImage = (string) ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        Log::debug('Prepared resized OCR image.', [
            'source_bytes' => $sourceBytes,
            'ocr_bytes' => strlen($optimizedImage),
            'source_dimensions' => "{$sourceWidth}x{$sourceHeight}",
            'ocr_dimensions' => "{$targetWidth}x{$targetHeight}",
        ]);

        return $optimizedImage;
    }

    private function decodeImageDataUrl(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(?:jpeg|png|webp);base64,(.+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'image' => 'The OCR preview image payload is invalid.',
            ]);
        }

        $bytes = base64_decode($matches[1], strict: true);

        if ($bytes === false) {
            throw ValidationException::withMessages([
                'image' => 'The OCR preview image could not be decoded.',
            ]);
        }

        if (strlen($bytes) > 1_500_000) {
            throw ValidationException::withMessages([
                'image' => 'The OCR preview image is too large.',
            ]);
        }

        return $bytes;
    }
}
