<?php

namespace Tests\Feature;

use App\Filament\Pages\CaptureBook;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CaptureBookCoverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Http::fake([
            'isbn.perpusnas.go.id/*' => Http::response(['data' => []]),
            'khrlanamm.github.io/*' => Http::response([], 404),
            'www.googleapis.com/*' => Http::response(['items' => []]),
            'openlibrary.org/*' => Http::response([], 200),
        ]);
        $this->actingAs(User::factory()->create());
    }

    public function test_local_isbn_match_loads_existing_book_cover_and_stops_scanner_panel(): void
    {
        $book = Book::factory()->create([
            'isbn13' => '9781234567890',
            'cover_image_path' => 'book-covers/existing/cover.jpg',
        ]);
        Storage::disk('public')->put($book->cover_image_path, 'cover bytes');

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('matchedBookId', $book->id)
            ->assertSet('coverPanelVisible', true)
            ->assertSet('matchedBookCoverUrl', route('storage.public', ['path' => $book->cover_image_path]));
    }

    public function test_local_isbn_without_cover_shows_placeholder(): void
    {
        $book = Book::factory()->create(['isbn13' => '9781234567890']);

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('matchedBookId', $book->id)
            ->assertSet('coverPanelVisible', true)
            ->assertSet('matchedBookCoverUrl', asset('images/book-cover-placeholder.svg'));
    }

    public function test_new_book_can_save_camera_or_file_cover_with_zero_copies(): void
    {
        $upload = UploadedFile::fake()->createWithContent('cover.png', 'valid image placeholder');

        Livewire::test(CaptureBook::class)
            ->set('isbnBarcodeValue', '9781234567890')
            ->set('bookTitle', 'New book with cover')
            ->set('bookAuthors', 'Ada Lovelace')
            ->set('authorNameStyle', 'foreign')
            ->set('categoryId', Category::factory()->create()->id)
            ->set('coverDraft', $upload)
            ->set('quantity', 0)
            ->call('submit')
            ->assertHasNoErrors();

        $book = Book::query()->where('title', 'New book with cover')->firstOrFail();

        $this->assertSame(0, $book->copies()->count());
        $this->assertSame('foreign', $book->author_name_style);
        $this->assertSame($book->category_id, Category::query()->value('id'));
        $this->assertNotNull($book->cover_image_path);
        $this->assertTrue(Storage::disk('public')->exists($book->cover_image_path));
    }

    public function test_capture_replaces_active_cover_for_local_book_and_deletes_old_managed_cover(): void
    {
        $oldPath = 'book-covers/01ABC/old.jpg';
        $book = Book::factory()->create([
            'isbn13' => '9781234567890',
            'cover_image_path' => $oldPath,
        ]);
        Storage::disk('public')->put($oldPath, 'old cover');
        $upload = UploadedFile::fake()->createWithContent('replacement.jpg', 'replacement image bytes');

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->set('coverDraft', $upload)
            ->set('quantity', 0)
            ->call('submit')
            ->assertHasNoErrors();

        $book->refresh();

        $this->assertNotSame($oldPath, $book->cover_image_path);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertTrue(Storage::disk('public')->exists($book->cover_image_path));
    }

    public function test_invalid_cover_file_is_rejected_without_creating_book(): void
    {
        Livewire::test(CaptureBook::class)
            ->set('bookTitle', 'Invalid image cover')
            ->set('coverDraft', UploadedFile::fake()->create('cover.pdf', 100, 'application/pdf'))
            ->assertHasErrors(['coverDraft']);

        $this->assertDatabaseMissing('books', ['title' => 'Invalid image cover']);
        $this->assertSame([], Storage::disk('public')->allFiles('book-covers'));
    }
}
