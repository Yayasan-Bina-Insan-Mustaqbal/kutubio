<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OllamaService
{
    protected string $baseUrl;

    protected string $model;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url');
        $this->model = config('services.ollama.vision_model');
    }

    /**
     * @param  string  $imagePath  Relative path in 'public' disk
     *
     * @throws Exception
     */
    public function extractFromImage(string $imagePath, string $prompt): array
    {
        if (! Storage::disk('public')->exists($imagePath)) {
            throw new Exception("Image not found: {$imagePath}");
        }

        return $this->extractFromImageBytes(Storage::disk('public')->get($imagePath), $prompt);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function extractFromImageBytes(string $imageBytes, string $prompt): array
    {
        $response = Http::timeout(60)->post("{$this->baseUrl}/api/generate", [
            'model' => $this->model,
            'prompt' => $prompt,
            'images' => [base64_encode($imageBytes)],
            'stream' => false,
            'format' => 'json',
        ]);

        if ($response->failed()) {
            throw new Exception('Ollama API request failed: '.$response->body());
        }

        return $response->json();
    }
}
