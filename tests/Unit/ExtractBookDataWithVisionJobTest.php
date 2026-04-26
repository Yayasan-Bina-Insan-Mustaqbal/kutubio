<?php

namespace Tests\Unit;

use App\Enums\CaptureSessionStatus;
use App\Jobs\ExtractBookDataWithVisionJob;
use App\Models\CaptureSession;
use App\Services\BookCoverOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ExtractBookDataWithVisionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_extracted_metadata_from_book_cover_ocr_service(): void
    {
        $captureSession = CaptureSession::factory()->create([
            'status' => CaptureSessionStatus::Captured,
            'front_image_path' => 'captures/front.jpg',
        ]);

        $bookCoverOcr = Mockery::mock(BookCoverOcrService::class);
        $bookCoverOcr->shouldReceive('extractFromStoragePath')
            ->once()
            ->with('captures/front.jpg')
            ->andReturn([
                'title' => 'Kesadaran Beramal',
                'subtitle' => 'Menumbuhkan',
                'authors' => ['Abdul Kholiq', 'Bayu Issetyadi'],
                'publisher' => 'HUD',
                'confidence' => 0.95,
            ]);

        (new ExtractBookDataWithVisionJob($captureSession))->handle($bookCoverOcr);

        $revision = $captureSession->metadataRevisions()->where('source_stage', 'vision_extraction')->firstOrFail();

        $this->assertSame('Kesadaran Beramal', $revision->payload['title']);
        $this->assertSame('Menumbuhkan', $revision->payload['subtitle']);
        $this->assertSame(['Abdul Kholiq', 'Bayu Issetyadi'], $revision->payload['authors']);
        $this->assertSame('HUD', $revision->payload['publisher']);
        $this->assertSame(CaptureSessionStatus::NeedsReview, $captureSession->fresh()->status);
    }
}
