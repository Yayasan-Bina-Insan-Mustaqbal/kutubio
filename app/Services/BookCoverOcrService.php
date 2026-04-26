<?php

namespace App\Services;

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
        return $this->metadataFromResponse(
            $this->ollama->extractFromImage($imagePath, $this->prompt()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function extractFromDataUrl(string $dataUrl): array
    {
        return $this->metadataFromResponse(
            $this->ollama->extractFromImageBytes($this->decodeImageDataUrl($dataUrl), $this->prompt()),
        );
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
