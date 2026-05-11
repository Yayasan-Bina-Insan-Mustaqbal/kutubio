<?php

namespace App\Jobs;

use App\Enums\CaptureSessionStatus;
use App\Enums\MetadataRevisionType;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SummarizeCaptureSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CaptureSession $captureSession
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OllamaService $ollama): void
    {
        Log::info("Starting SummarizeCaptureSessionJob for session {$this->captureSession->public_id}");

        if ($this->captureSession->status !== CaptureSessionStatus::Processing) {
            $this->captureSession->update([
                'status' => CaptureSessionStatus::Processing,
                'processing_started_at' => now(),
            ]);
        }

        $this->captureSession->update(['current_processing_stage' => 'Generating Summary...']);

        $revisions = $this->captureSession->metadataRevisions;

        $collectedData = [];
        foreach ($revisions as $revision) {
            $collectedData[] = [
                'type' => $revision->revision_type->value,
                'payload' => $revision->payload,
            ];
        }

        try {
            // We use the regular LLM model for summarization, not vision
            $prompt = 'Analyze the following collected metadata from a book capture session and provide a final summary and categorization. 
            Identify if any critical information is missing (Title, Author, ISBN/CopyID). 
            Also suggest the most appropriate DDC (Dewey Decimal Classification) category code (3 digits, e.g., 200, 300, 600).
            
            Collected Data: '.json_encode($collectedData)."
            
            Respond ONLY with a JSON object containing: 
            'summary' (string), 
            'missing_fields' (array of strings), 
            'is_ready_for_approval' (boolean),
            'category_code' (string, 3-digit DDC code).";

            // Note: For pure text tasks, we could use OLLAMA_LLM_MODEL
            // but for simplicity we'll use the OllamaService with no image.
            // I'll update OllamaService to support text-only.

            $response = Http::timeout(60)->post(config('services.ollama.url').'/api/generate', [
                'model' => config('services.ollama.llm_model', 'llama3.1:latest'),
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ]);

            if ($response->failed()) {
                throw new \Exception('Ollama API request failed: '.$response->body());
            }

            $result = $response->json();
            $data = json_decode($result['response'] ?? '{}', true);

            MetadataRevision::create([
                'capture_session_id' => $this->captureSession->id,
                'revision_type' => MetadataRevisionType::SystemMerge,
                'source_stage' => 'final_summary',
                'source_actor_type' => self::class,
                'source_actor_id' => 0,
                'payload' => $data,
                'source_meta' => [
                    'model' => config('services.ollama.llm_model', 'llama3.1:latest'),
                ],
            ]);

            $this->captureSession->update([
                'status' => CaptureSessionStatus::NeedsReview,
                'current_processing_stage' => null,
                'processing_finished_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Summary generation failed for session {$this->captureSession->public_id}: ".$e->getMessage());

            $this->captureSession->update([
                'status' => CaptureSessionStatus::Failed,
                'failure_reason' => 'Summary failed: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }
}
