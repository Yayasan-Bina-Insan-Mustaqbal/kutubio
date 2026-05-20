<?php

namespace Tests\Feature;

use App\Enums\CaptureSessionStatus;
use App\Enums\MetadataRevisionType;
use App\Filament\Pages\CaptureBook;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Models\User;
use App\Services\BookCoverOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class CaptureBookPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_render_capture_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(CaptureBook::getUrl())->assertOk();
    }

    public function test_submit_requires_front_image(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('submit')
            ->assertHasErrors(['frontImageData' => 'required']);
    }

    public function test_submit_requires_isbn_barcode_value_to_be_numeric_when_present(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->set('frontImageData', $this->imageDataUrl())
            ->set('isbnBarcodeValue', '978-abc')
            ->call('submit')
            ->assertHasErrors(['isbnBarcodeValue' => 'regex']);
    }

    public function test_submit_creates_capture_session_and_raw_revision_without_back_image(): void
    {
        Queue::fake();
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CaptureBook::class)
            ->set('frontImageData', $this->imageDataUrl())
            ->set('frontImageWidth', 1)
            ->set('frontImageHeight', 1)
            ->set('isbnBarcodeValue', '9781234567890')
            ->set('quantity', 3)
            ->set('fundingSource', 'BOS')
            ->set('purchaseYear', '20230')
            ->call('submit')
            ->assertRedirect();

        $captureSession = CaptureSession::firstOrFail();

        $this->assertSame($user->id, $captureSession->submitted_by);
        $this->assertSame(CaptureSessionStatus::Processing, $captureSession->status);
        $this->assertSame(3, $captureSession->quantity);
        $this->assertNotNull($captureSession->submitted_at);
        $this->assertSame(['mime_type' => 'image/png', 'size_bytes' => 68, 'width' => 1, 'height' => 1], $captureSession->front_image_meta);
        $this->assertSame([
            'barcode_value' => '9781234567890',
            'barcode_type' => '1d',
        ], $captureSession->back_image_meta);
        $this->assertNull($captureSession->back_image_path);

        Storage::disk('public')->assertExists($captureSession->front_image_path);

        $revision = MetadataRevision::firstOrFail();

        $this->assertSame($captureSession->id, $revision->capture_session_id);
        $this->assertSame(MetadataRevisionType::RawCapture, $revision->revision_type);
        $this->assertSame('capture_page', $revision->source_stage);
        $this->assertSame($captureSession->front_image_path, $revision->payload['front_image_path']);
        $this->assertArrayNotHasKey('back_image_path', $revision->payload);
        $this->assertSame('9781234567890', $revision->payload['isbn_barcode_value']);
        $this->assertSame(3, $revision->payload['quantity']);
        $this->assertSame('BOS', $revision->payload['funding_source']);
        $this->assertSame('20230', $revision->payload['purchase_year']);
    }

    public function test_front_ocr_preview_returns_metadata_without_storing_capture(): void
    {
        $this->actingAs(User::factory()->create());

        $bookCoverOcr = Mockery::mock(BookCoverOcrService::class);
        $bookCoverOcr->shouldReceive('extractFromDataUrl')
            ->once()
            ->with($this->imageDataUrl())
            ->andReturn([
                'title' => 'Kesadaran Beramal',
                'subtitle' => 'Menumbuhkan',
                'authors' => ['Abdul Kholiq', 'Bayu Issetyadi'],
                'publisher' => 'HUD',
                'confidence' => 0.95,
                'ocr_text' => "MENUMBUHKAN\nKESADARAN\nBERAMAL",
            ]);

        $this->app->instance(BookCoverOcrService::class, $bookCoverOcr);

        Livewire::test(CaptureBook::class)
            ->call('previewFrontOcr', $this->imageDataUrl())
            ->assertReturned([
                'ok' => true,
                'metadata' => [
                    'title' => 'Kesadaran Beramal',
                    'subtitle' => 'Menumbuhkan',
                    'authors' => ['Abdul Kholiq', 'Bayu Issetyadi'],
                    'publisher' => 'HUD',
                    'confidence' => 0.95,
                    'ocr_text' => "MENUMBUHKAN\nKESADARAN\nBERAMAL",
                ],
            ]);

        $this->assertSame(0, CaptureSession::count());
    }

    public function test_submit_stores_accepted_realtime_ocr_revision(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CaptureBook::class)
            ->set('frontImageData', $this->imageDataUrl())
            ->set('isbnBarcodeValue', '9781234567890')
            ->set('frontOcrTitle', 'Kesadaran Beramal')
            ->set('frontOcrSubtitle', 'Menumbuhkan')
            ->set('frontOcrAuthors', 'Abdul Kholiq, Bayu Issetyadi')
            ->set('frontOcrPublisher', 'HUD')
            ->set('frontOcrText', "MENUMBUHKAN\nKESADARAN\nBERAMAL")
            ->set('frontOcrConfidence', '0.95')
            ->call('submit')
            ->assertRedirect();

        $captureSession = CaptureSession::firstOrFail();
        $revision = $captureSession->metadataRevisions()
            ->where('source_stage', 'vision_extraction')
            ->firstOrFail();

        $this->assertSame(MetadataRevisionType::LlmDraft, $revision->revision_type);
        $this->assertSame('Kesadaran Beramal', $revision->payload['title']);
        $this->assertSame(['Abdul Kholiq', 'Bayu Issetyadi'], $revision->payload['authors']);
        $this->assertSame('HUD', $revision->payload['publisher']);
        $this->assertSame('0.9500', $revision->confidence_score);
    }

    private function imageDataUrl(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';
    }
}
