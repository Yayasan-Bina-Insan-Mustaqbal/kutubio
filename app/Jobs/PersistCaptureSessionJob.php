<?php

namespace App\Jobs;

use App\Enums\BookCopyStatus;
use App\Enums\CaptureSessionStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\CaptureSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersistCaptureSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CaptureSession $captureSession
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting PersistCaptureSessionJob for session {$this->captureSession->public_id}");

        $this->captureSession->update(['current_processing_stage' => 'Persisting Records...']);

        // Fetch latest metadata from revisions
        $visionData = $this->captureSession->metadataRevisions()
            ->where('source_stage', 'vision_extraction')
            ->latest()
            ->first()?->payload ?? [];

        // Fallback to title from other revisions (like manual capture page entry)
        if (empty($visionData['title'])) {
            $anyTitle = $this->captureSession->metadataRevisions()
                ->whereNotNull('payload->title')
                ->latest()
                ->first()?->payload['title'] ?? null;

            if ($anyTitle) {
                $visionData['title'] = $anyTitle;
            }
        }

        $qrData = $this->captureSession->metadataRevisions()
            ->where('source_stage', 'qr_reading')
            ->latest()
            ->first()?->payload ?? [];

        // Fallback to ISBN from back_image_meta or raw capture
        $isbn = $qrData['decoded_text'] ?? null;
        if (! $isbn) {
            $isbn = $this->captureSession->back_image_meta['barcode_value'] ?? null;
        }
        if (! $isbn) {
            $isbn = $this->captureSession->metadataRevisions()
                ->where('source_stage', 'capture_page')
                ->latest()
                ->first()?->payload['isbn'] ?? null;
        }

        if (empty($visionData['title'])) {
            throw new \Exception('Cannot persist: No book title found in metadata revisions.');
        }

        DB::transaction(function () use ($visionData, $isbn) {
            // 1. Find or Create Book
            $book = null;
            if ($isbn) {
                $book = Book::where('isbn13', $isbn)->first();
            }

            if (! $book) {
                $authors = $visionData['authors'] ?? [];
                $authorsDisplay = is_array($authors) ? implode(', ', $authors) : $authors;

                $book = Book::create([
                    'title' => $visionData['title'],
                    'authors_display' => $authorsDisplay,
                    'isbn13' => $isbn,
                    'publisher' => $visionData['publisher'] ?? null,
                    'subtitle' => $visionData['subtitle'] ?? null,
                ]);
            }

            // 2. Create Book Copies based on quantity
            for ($i = 0; $i < $this->captureSession->quantity; $i++) {
                BookCopy::create([
                    'book_id' => $book->id,
                    'status' => BookCopyStatus::Draft,
                    'acquired_at' => now(),
                ]);
            }

            // 3. Update Capture Session
            $this->captureSession->update([
                'status' => CaptureSessionStatus::Approved,
                'current_processing_stage' => null,
                'processing_finished_at' => now(),
            ]);

            // 4. Link Revisions to Book
            $this->captureSession->metadataRevisions()->update(['book_id' => $book->id]);
        });
    }
}
