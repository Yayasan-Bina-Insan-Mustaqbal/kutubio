<?php

namespace Tests\Feature;

use App\Enums\MetadataRevisionType;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_import_preserves_ddc_codes_as_strings(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseHas('categories', [
            'code' => '000',
            'label' => 'Ilmu Komputer, Pengetahuan, Sistem',
            'source_version' => 'tier2DDC',
        ]);
    }

    public function test_book_has_many_copies(): void
    {
        $book = Book::factory()->create();
        BookCopy::factory()->count(2)->create(['book_id' => $book->id]);

        $this->assertCount(2, $book->fresh()->copies);
    }

    public function test_duplicate_public_id_fails_fast(): void
    {
        $publicId = '01HX0000000000000000000000';

        Book::factory()->create(['public_id' => $publicId]);

        $this->expectException(QueryException::class);

        Book::factory()->create(['public_id' => $publicId]);
    }

    public function test_duplicate_qr_payload_fails_fast(): void
    {
        $qrPayload = 'kutubio:copy:v1:01HX0000000000000000000000';

        BookCopy::factory()->create(['qr_payload' => $qrPayload]);

        $this->expectException(QueryException::class);

        BookCopy::factory()->create(['qr_payload' => $qrPayload]);
    }

    public function test_capture_session_has_many_metadata_revisions(): void
    {
        $captureSession = CaptureSession::factory()->create();
        MetadataRevision::factory()->count(2)->create([
            'capture_session_id' => $captureSession->id,
        ]);

        $this->assertCount(2, $captureSession->fresh()->metadataRevisions);
    }

    public function test_metadata_revisions_append_history_without_overwriting_payloads(): void
    {
        $book = Book::factory()->create();

        MetadataRevision::factory()->create([
            'book_id' => $book->id,
            'revision_type' => MetadataRevisionType::RawCapture,
            'payload' => ['title' => 'Raw title'],
        ]);

        MetadataRevision::factory()->create([
            'book_id' => $book->id,
            'revision_type' => MetadataRevisionType::HumanReviewed,
            'payload' => ['title' => 'Approved title'],
        ]);

        $this->assertSame(
            ['title' => 'Raw title'],
            $book->metadataRevisions()->oldest()->first()->payload,
        );

        $this->assertSame(2, $book->metadataRevisions()->count());
    }

    public function test_book_resolves_approved_metadata_revision(): void
    {
        $book = Book::factory()->create();
        $revision = MetadataRevision::factory()->create([
            'book_id' => $book->id,
            'revision_type' => MetadataRevisionType::HumanReviewed,
            'payload' => ['title' => $book->title],
        ]);

        $book->update(['approved_metadata_revision_id' => $revision->id]);

        $this->assertTrue($revision->is($book->fresh()->approvedMetadataRevision));
    }

    public function test_book_copy_resolves_funding_source_and_purchase_year(): void
    {
        $book = Book::factory()->create();

        MetadataRevision::factory()->create([
            'book_id' => $book->id,
            'source_stage' => 'capture_page',
            'payload' => [
                'funding_source' => 'BOS',
                'purchase_year' => '2024',
            ],
        ]);

        $copy = BookCopy::factory()->create(['book_id' => $book->id]);

        $this->assertSame('BOS (Gov-Fund)', $copy->funding_source);
        $this->assertSame('2024', $copy->purchase_year);
    }
}
