<?php

namespace Tests\Feature;

use App\Enums\MetadataRevisionType;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Models\User;
use App\Policies\BookCopyPolicy;
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

    public function test_all_user_roles_can_delete_book_copies(): void
    {
        $policy = new BookCopyPolicy();
        $staffUser = User::factory()->create(['role' => 'staff']);
        $adminUser = User::factory()->create(['role' => 'admin']);
        $copy = BookCopy::factory()->create();

        $this->assertTrue($policy->deleteAny($staffUser));
        $this->assertTrue($policy->delete($staffUser, $copy));
        $this->assertTrue($policy->deleteAny($adminUser));
        $this->assertTrue($policy->delete($adminUser, $copy));
    }

    public function test_book_has_many_copies(): void
    {
        $book = Book::factory()->create();
        BookCopy::factory()->count(2)->create(['book_id' => $book->id]);

        $this->assertCount(2, $book->fresh()->copies);
    }

    public function test_soft_deleting_book_soft_deletes_its_copies(): void
    {
        $book = Book::factory()->create();
        $copies = BookCopy::factory()->count(2)->create(['book_id' => $book->id]);

        $book->delete();

        $this->assertSoftDeleted($book);

        foreach ($copies as $copy) {
            $this->assertSoftDeleted($copy);
        }
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

    public function test_uniform_stored_value_normalizes_funding_keys_and_returns_null_for_mixed_values(): void
    {
        $book = Book::factory()->create();
        $localCopy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'funding_source' => 'self',
        ]);
        $secondLocalCopy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'funding_source' => 'Self-Fund',
        ]);
        $governmentCopy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'funding_source' => 'BOSP',
        ]);

        $this->assertSame('self', BookCopy::uniformStoredValue(collect([$localCopy, $secondLocalCopy]), 'funding_source'));
        $this->assertNull(BookCopy::uniformStoredValue(collect([$localCopy, $governmentCopy]), 'funding_source'));
    }

    public function test_bulk_update_preserves_borrowed_status_and_unselected_fields(): void
    {
        $book = Book::factory()->create();
        $borrowedCopy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'status' => \App\Enums\BookCopyStatus::Borrowed,
            'funding_source' => 'self',
            'purchase_year' => '2024',
        ]);
        $availableCopy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'status' => \App\Enums\BookCopyStatus::Available,
            'funding_source' => 'self',
            'purchase_year' => '2024',
        ]);

        $borrowedCopy->applyBulkUpdates([
            'funding_source' => 'BOSP',
            'status' => \App\Enums\BookCopyStatus::Available->value,
        ], \App\Enums\BookCopyStatus::Available);
        $availableCopy->applyBulkUpdates(['funding_source' => 'BOSP'], \App\Enums\BookCopyStatus::Available);

        $this->assertSame(\App\Enums\BookCopyStatus::Borrowed, $borrowedCopy->fresh()->status);
        $this->assertSame(\App\Enums\BookCopyStatus::Available, $availableCopy->fresh()->status);
        $this->assertSame('BOSP (Gov-Fund)', $borrowedCopy->fresh()->funding_source);
        $this->assertSame('2024', $borrowedCopy->fresh()->purchase_year);
    }

    public function test_copy_fields_are_independently_persisted(): void
    {
        $book = Book::factory()->create();
        $copy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'funding_source' => 'BOSP',
            'purchase_year' => '2025',
        ]);

        $copy->update(['tracking_code' => 'TRACK-01']);

        $copy->refresh();

        $this->assertSame('BOSP (Gov-Fund)', $copy->funding_source);
        $this->assertSame('2025', $copy->purchase_year);
        $this->assertSame('TRACK-01', $copy->tracking_code);
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

        $copy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'funding_source' => 'BOS',
            'purchase_year' => '2024',
        ]);

        $this->assertSame('BOSP (Gov-Fund)', $copy->funding_source);
        $this->assertSame('2024', $copy->purchase_year);
    }
}
