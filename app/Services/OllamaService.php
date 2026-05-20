<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        Log::info("OllamaService: Requesting extraction with model {$this->model}");

        $response = Http::timeout(60)->post("{$this->baseUrl}/api/generate", [
            'model' => $this->model,
            'prompt' => $prompt,
            'images' => [base64_encode($imageBytes)],
            'stream' => false,
            'format' => 'json',
        ]);

        if ($response->failed()) {
            Log::error('OllamaService: Request failed: '.$response->body());
            throw new Exception('Ollama API request failed: '.$response->body());
        }

        $result = $response->json();
        Log::info('OllamaService: Received response: '.($result['response'] ?? 'EMPTY'));

        return $result;
    }

    /**
     * Classify a book using standard Dewey Decimal Classification (DDC).
     */
    public function classifyBook(string $title, ?string $authors): ?string
    {
        try {
            $prompt = "You are a professional library cataloger. Classify this book into the standard Dewey Decimal Classification (DDC) 3-digit main classes.
Title: {$title}
Authors: {$authors}

Standard DDC main classes:
000 - Computer science, information & general works
100 - Philosophy & psychology
200 - Religion
300 - Social sciences
400 - Language
500 - Science
600 - Technology (Applied sciences)
700 - Arts & recreation
800 - Literature
900 - History & geography

Respond ONLY with a JSON object containing a single field 'category_code' (a 3-digit string representing the most appropriate main class, e.g., '200' or '600'). Do not write any other explanation.";

            $response = Http::timeout(30)->post("{$this->baseUrl}/api/generate", [
                'model' => config('services.ollama.llm_model', 'llama3.1:latest'),
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $data = json_decode($result['response'] ?? '{}', true);

                return $data['category_code'] ?? null;
            }
        } catch (Exception $e) {
            Log::warning('OllamaService classification failed: '.$e->getMessage());
        }

        return null;
    }
}
