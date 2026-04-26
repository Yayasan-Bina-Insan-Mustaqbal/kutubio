<?php

namespace App\Jobs;

use App\Enums\CaptureSessionStatus;
use App\Enums\MetadataRevisionType;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Services\BookCoverOcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExtractBookDataWithVisionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
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
    public function handle(BookCoverOcrService $bookCoverOcr): void
    {
        Log::info("Starting ExtractBookDataWithVisionJob for session {$this->captureSession->public_id}");

        if ($this->captureSession->status !== CaptureSessionStatus::Processing) {
            $this->captureSession->update([
                'status' => CaptureSessionStatus::Processing,
                'failure_reason' => null,
                'processing_started_at' => now(),
            ]);
        }

        $this->captureSession->update(['current_processing_stage' => 'Extracting Title...']);

        if (! $this->captureSession->front_image_path) {
            Log::warning("Capture session {$this->captureSession->public_id} has no front image for vision extraction.");

            return;
        }

        try {
            $data = $bookCoverOcr->extractFromStoragePath($this->captureSession->front_image_path);

            if (empty($data['title'])) {
                throw new \Exception('Could not extract title from image.');
            }

            MetadataRevision::create([
                'capture_session_id' => $this->captureSession->id,
                'revision_type' => MetadataRevisionType::LlmDraft,
                'source_stage' => 'vision_extraction',
                'source_actor_type' => self::class,
                'source_actor_id' => 0, // System
                'payload' => array_merge($data, [
                    'front_image_path' => $this->captureSession->front_image_path,
                    'notes' => 'Automatic vision extraction using '.config('services.ollama.vision_model', 'glm-ocr'),
                ]),
                'source_meta' => [
                    'model' => config('services.ollama.vision_model', 'glm-ocr'),
                ],
            ]);

            $this->captureSession->update([
                'status' => CaptureSessionStatus::NeedsReview,
                'current_processing_stage' => null,
                'processing_finished_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Vision extraction failed for session {$this->captureSession->public_id}: ".$e->getMessage());

            $this->captureSession->update([
                'status' => CaptureSessionStatus::Failed,
                'failure_reason' => 'AI Vision extraction failed: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }
}
