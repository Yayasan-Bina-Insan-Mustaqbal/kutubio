<?php

namespace Tests\Feature;

use App\Filament\Pages\CaptureBook;
use App\Filament\Pages\ScanBook;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CaptureBookPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_render_isbn_first_capture_form(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(CaptureBook::getUrl());
        $response->assertOk()
            ->assertSee('capture-book-layout', false)
            ->assertSee('capture-book-camera-stage', false)
            ->assertSee('ISBN Scanner')
            ->assertSee('Capture Book')
            ->assertSee('Scan Book')
            ->assertSee('Restart / Rescan ISBN')
            ->assertSee('Look Up ISBN')
            ->assertSee('Bibliographic Details')
            ->assertSee('Copy Intake')
            ->assertSee('Author Name Style')
            ->assertSee('wire:model="authorNameStyle"', false)
            ->assertSee('Author(s) <span class="text-danger-600">*</span>', false)
            ->assertSee('wire:model="bookAuthors"', false)
            ->assertSee('required', false)
            ->assertSee('Category')
            ->assertSee('Search category name or DDC code')
            ->assertSee('wire:model.live.debounce.300ms="categorySearch"', false)
            ->assertSee('aria-haspopup="listbox"', false)
            ->assertSee('Uncategorized')
            ->assertSee('value="local"', false)
            ->assertSee('id="barcodeScanner"', false)
            ->assertSee('data-mode="scanner"', false)
            ->assertSee('class="mt-4 mb-2 grid grid-cols-2 gap-2 hidden"', false)
            ->assertSee('id="coverFileName"', false)
            ->assertSee('type="file" accept="image/jpeg,image/png,image/webp" aria-label="Choose cover image" class="hidden"', false)
            ->assertSee('id="rescanBarcodeButton"', false)
            ->assertSee('wire:click="resetCapture"', false)
            ->assertSee('wire:click="resetCapture"', false)
            ->assertDontSee('Book Title Extraction')
            ->assertDontSee('Author Name Extraction');
    }

    public function test_capture_and_scan_page_navigation_labels_are_distinct(): void
    {
        $this->assertSame('Capture Book', CaptureBook::getNavigationLabel());
        $this->assertSame('Scan Book', ScanBook::getNavigationLabel());
    }

    public function test_submit_saves_manual_synopsis_on_the_book(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->set('isbnBarcodeValue', '9781234567890')
            ->set('bookTitle', 'Book with synopsis')
            ->set('bookAuthors', 'Author Name')
            ->set('bookSynopsis', 'A short description of the book.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('books', [
            'title' => 'Book with synopsis',
            'synopsis' => 'A short description of the book.',
        ]);
    }

    public function test_submit_requires_book_title_author_and_validates_optional_category(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->set('bookTitle', 'Book without author')
            ->call('submit')
            ->assertHasErrors(['bookAuthors' => 'required']);

        $this->assertSame(0, Book::count());

        Livewire::test(CaptureBook::class)
            ->set('bookTitle', 'Book with author')
            ->set('bookAuthors', 'Ada Lovelace')
            ->set('categoryId', 999999)
            ->call('submit')
            ->assertHasErrors(['categoryId' => 'exists']);

        $this->assertSame(0, Book::count());
    }

    public function test_category_search_matches_name_and_ddc_code(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create([
            'code' => '641.5',
            'label' => 'Food and cooking',
            'short_label' => 'Cooking',
        ]);
        Category::factory()->create([
            'code' => '005',
            'label' => 'Computer programming',
            'short_label' => 'Programming',
        ]);

        Livewire::test(CaptureBook::class)
            ->set('categorySearch', '641.5')
            ->assertSee('641.5 — Food and cooking')
            ->assertDontSee('Computer programming')
            ->set('categorySearch', 'Cooking')
            ->assertSee('641.5 — Food and cooking')
            ->assertSet('categoryId', null)
            ->call('selectCategory', $category->id)
            ->assertSet('categoryId', $category->id)
            ->assertSet('categorySearch', null)
            ->set('bookTitle', 'Searchable category book')
            ->set('bookAuthors', 'Category Author')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('books', [
            'title' => 'Searchable category book',
            'category_id' => $category->id,
        ]);
    }

    public function test_reset_capture_clears_all_form_state_before_scanning_another_isbn(): void
    {
        $this->actingAs(User::factory()->create());

        $category = Category::factory()->create();

        Livewire::test(CaptureBook::class)
            ->set('isbnBarcodeValue', '9781234567890')
            ->set('bookTitle', 'Previous title')
            ->set('bookSubtitle', 'Previous subtitle')
            ->set('bookSynopsis', 'Previous synopsis')
            ->set('bookAuthors', 'Previous author')
            ->set('authorNameStyle', 'foreign')
            ->set('categoryId', $category->id)
            ->set('categorySearch', 'History')
            ->set('bookPublisher', 'Previous publisher')
            ->set('bookPageCount', 42)
            ->set('matchedBookId', 1)
            ->set('matchedBookCoverUrl', 'https://example.test/previous-cover.jpg')
            ->set('coverPanelVisible', true)
            ->set('metadataLookupState', 'local_match')
            ->set('metadataLookupMessage', 'Previous lookup result')
            ->set('quantity', 3)
            ->set('fundingSource', 'BOSP')
            ->set('purchaseYear', '2025')
            ->call('resetCapture')
            ->assertSet('isbnBarcodeValue', null)
            ->assertSet('bookTitle', null)
            ->assertSet('bookSubtitle', null)
            ->assertSet('bookSynopsis', null)
            ->assertSet('bookAuthors', null)
            ->assertSet('authorNameStyle', 'local')
            ->assertSet('categoryId', null)
            ->assertSet('categorySearch', null)
            ->assertSet('bookPublisher', null)
            ->assertSet('bookPageCount', null)
            ->assertSet('matchedBookId', null)
            ->assertSet('matchedBookCoverUrl', null)
            ->assertSet('coverPanelVisible', false)
            ->assertSet('metadataLookupState', 'idle')
            ->assertSet('metadataLookupMessage', '')
            ->assertSet('quantity', 0)
            ->assertSet('fundingSource', 'self')
            ->assertSet('purchaseYear', (string) now()->year);
    }

    public function test_quantity_is_a_manual_numeric_input_without_increment_controls(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(CaptureBook::getUrl());
        $response->assertOk()
            ->assertSee('wire:model.live.number="quantity"', false)
            ->assertDontSee('$wire.quantity + 1', false)
            ->assertDontSee('$wire.quantity - 1', false);
    }

    public function test_submit_requires_book_title_without_requiring_capture_image(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('submit')
            ->assertHasErrors(['bookTitle' => 'required']);

        $this->assertSame(0, Book::count());
    }

    public function test_submit_requires_isbn_to_be_numeric_when_present(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->set('isbnBarcodeValue', '978-abc')
            ->set('bookTitle', 'Test title')
            ->call('submit')
            ->assertHasErrors(['isbnBarcodeValue' => 'regex']);
    }

    public function test_google_books_api_error_is_reported_and_open_library_fallback_runs(): void
    {
        Http::fake([
            'khrlanamm.github.io/buku-indonesia/api/v1/isbn/9781234567890.json' => Http::response([], 404),
            'www.googleapis.com/books/v1/volumes*' => Http::response(['error' => ['message' => 'API key invalid']], 403),
            'openlibrary.org/api/books*' => Http::response([], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('metadataLookupState', 'not_found')
            ->assertSee('Google Books API key was rejected or lacks permission')
            ->assertSee('ISBN was not found in Google Books or Open Library');
    }

    public function test_isbn_lookup_fills_draft_from_local_book(): void
    {
        $this->actingAs(User::factory()->create());
        $book = \App\Models\Book::factory()->create([
            'isbn13' => '9781234567890',
            'title' => 'Local title',
            'authors_display' => 'Local author',
        ]);

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('matchedBookId', $book->id)
            ->assertSet('bookTitle', 'Local title')
            ->assertSet('metadataLookupState', 'local_match');
    }

    public function test_refresh_metadata_previews_provider_values_and_applies_only_to_empty_form_fields(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response([
                'data' => [[
                    'isbn' => '9786020935539',
                    'title' => 'Provider title',
                    'sinopsis' => 'Provider synopsis',
                    'kepeng' => 'Provider Author',
                    'nama_penerbit' => 'Provider Publisher',
                ]],
            ]),
        ]);
        $this->actingAs(User::factory()->create());
        $book = Book::factory()->create([
            'isbn13' => '9786020935539',
            'title' => 'Saved title',
            'authors_display' => 'Saved author',
            'synopsis' => null,
            'publisher' => null,
        ]);

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', $book->isbn13)
            ->assertSet('bookTitle', 'Saved title')
            ->assertSet('bookSynopsis', null)
            ->call('refreshBookMetadata')
            ->assertSet('metadataRefreshAvailable', true)
            ->assertSet('bookTitle', 'Saved title')
            ->assertSet('bookSynopsis', null)
            ->call('applyMetadataRefresh')
            ->assertSet('bookTitle', 'Saved title')
            ->assertSet('bookAuthors', 'Saved author')
            ->assertSet('bookSynopsis', 'Provider synopsis')
            ->assertSet('bookPublisher', 'Provider Publisher')
            ->assertSet('metadataRefreshAvailable', false)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Saved title',
            'authors_display' => 'Saved author',
            'synopsis' => 'Provider synopsis',
            'publisher' => 'Provider Publisher',
        ]);
    }

    public function test_refresh_metadata_without_provider_description_keeps_existing_values_and_does_not_save(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response(['data' => []]),
            'khrlanamm.github.io/*' => Http::response([], 404),
            'www.googleapis.com/*' => Http::response(['items' => []], 200),
            'openlibrary.org/*' => Http::response([], 200),
        ]);
        $this->actingAs(User::factory()->create());
        $book = Book::factory()->create([
            'isbn13' => '9786020935539',
            'title' => 'Saved title',
            'authors_display' => 'Saved author',
            'synopsis' => 'Existing synopsis stays.',
        ]);

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', $book->isbn13)
            ->call('refreshBookMetadata')
            ->assertSet('metadataRefreshAvailable', false)
            ->assertSet('metadataRefreshMessage', 'No provider metadata was found. Existing book details were not changed.')
            ->assertSet('bookTitle', 'Saved title')
            ->assertSet('bookSynopsis', 'Existing synopsis stays.');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Saved title',
            'synopsis' => 'Existing synopsis stays.',
        ]);
    }

    public function test_isbn_lookup_uses_perpusnas_json_metadata_before_other_external_sources(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response([
                'recordsTotal' => 1,
                'recordsFiltered' => 1,
                'data' => [[
                    'isbn' => '9786020935539',
                    'title' => 'ANAK YANG RAJIN DAN BAIK',
                    'sinopsis' => '<p>Kisah tentang anak rajin dan baik.</p>',
                    'kepeng' => '<span>Author One</span>, Author Two; <b>Author Three</b>, Ilustrasi, Budi Santoso',
                    'nama_penerbit' => '<span>Publisher Name</span>',
                    'tahun_terbit' => '<span>2024</span>',
                ]],
            ]),
            'khrlanamm.github.io/*' => Http::response([], 404),
            'www.googleapis.com/*' => Http::response(['items' => []], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9786020935539')
            ->assertSet('bookTitle', 'Anak yang Rajin dan Baik')
            ->assertSet('bookSynopsis', 'Kisah tentang anak rajin dan baik.')
            ->assertSet('bookAuthors', 'Author One, Author Two, Author Three')
            ->assertSet('bookPublisher', 'Publisher Name')
            ->assertSet('metadataLookupState', 'external_match')
            ->assertSee('Metadata found in Perpusnas ISBN');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/landing_page/serverside_search2')
            && $request['search'] === '9786020935539'
            && $request['filter_by'] === 'code'
            && $request['start'] === 0
            && $request['length'] === 10
            && $request->hasHeader('User-Agent'));
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'googleapis.com'));
    }

    public function test_perpusnas_missing_synopsis_leaves_capture_synopsis_empty(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response([
                'recordsTotal' => 1,
                'recordsFiltered' => 1,
                'data' => [[
                    'isbn' => '9786020935539',
                    'title' => 'ANAK YANG RAJIN DAN BAIK',
                    'kepeng' => 'Author One',
                    'nama_penerbit' => 'Publisher Name',
                ]],
            ]),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9786020935539')
            ->assertSet('bookSynopsis', null);
    }

    public function test_perpusnas_author_credits_are_removed_from_author_name(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response([
                'recordsTotal' => 1,
                'recordsFiltered' => 1,
                'data' => [[
                    'isbn' => '9786020935539',
                    'title' => '  KISAH   ANAK YANG CERDAS DI INDONESIA: PANDUAN UNTUK GURU  ',
                    'kepeng' => '<span>Shelomi Angeli</span>, Ilustrasi: Budi Santoso, Editor: R. Lestari',
                    'nama_penerbit' => 'Test Publisher',
                    'tahun_terbit' => '2025',
                ]],
            ]),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9786020935539')
            ->assertSet('bookTitle', 'Kisah Anak yang Cerdas di Indonesia: Panduan untuk Guru')
            ->assertSet('bookAuthors', 'Shelomi Angeli');
    }

    public function test_imported_title_casing_preserves_conjunctions_acronyms_and_punctuation(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response([
                'recordsTotal' => 1,
                'recordsFiltered' => 1,
                'data' => [[
                    'isbn' => '9786020935539',
                    'title' => 'SEJARAH ISBN DAN BUDAYA DI INDONESIA: PANDUAN UNTUK SISWA',
                    'kepeng' => 'R. Santoso',
                    'nama_penerbit' => 'Test Publisher',
                    'tahun_terbit' => '2025',
                ]],
            ]),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9786020935539')
            ->assertSet('bookTitle', 'Sejarah ISBN dan Budaya di Indonesia: Panduan untuk Siswa');
    }

    public function test_perpusnas_disusun_oleh_credit_prefix_is_removed(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response([
                'recordsTotal' => 1,
                'recordsFiltered' => 1,
                'data' => [[
                    'isbn' => '9786020935539',
                    'title' => 'Perpusnas Test Title',
                    'kepeng' => 'Disusun oleh Tim Erlangga',
                    'nama_penerbit' => 'Test Publisher',
                    'tahun_terbit' => '2025',
                ]],
            ]),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9786020935539')
            ->assertSet('bookAuthors', 'Tim Erlangga');
    }
    public function test_perpusnas_miss_falls_through_to_existing_isbn_providers(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response(['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]),
            'khrlanamm.github.io/buku-indonesia/api/v1/isbn/9781234567890.json' => Http::response([], 404),
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [['volumeInfo' => ['title' => 'Google Fallback Title', 'authors' => ['Google Author']]]],
            ], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('bookTitle', 'Google Fallback Title')
            ->assertSet('metadataLookupState', 'external_match');
    }

    public function test_perpusnas_malformed_response_is_ignored_safely(): void
    {
        Http::fake([
            'isbn.perpusnas.go.id/landing_page/serverside_search2*' => Http::response(['recordsTotal' => 1, 'recordsFiltered' => 1, 'data' => 'unexpected']),
            'khrlanamm.github.io/buku-indonesia/api/v1/isbn/9781234567890.json' => Http::response([], 404),
            'www.googleapis.com/books/v1/volumes*' => Http::response(['items' => []], 200),
            'openlibrary.org/api/books*' => Http::response([], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('metadataLookupState', 'not_found')
            ->assertSet('bookTitle', null);
    }

    public function test_isbn_lookup_prefers_indonesian_book_dataset_before_google_books(): void
    {
        Http::fake([
            'khrlanamm.github.io/buku-indonesia/api/v1/isbn/9781234567890.json' => Http::response([
                'title' => 'BUKU DAN KISAH YANG MENARIK',
                'subtitle' => null,
                'authors' => ['Dataset Author'],
                'publisher' => 'Dataset Publisher',
                'pages' => 222,
                'reviewed' => true,
            ], 200),
            'www.googleapis.com/books/v1/volumes*' => Http::response(['items' => []], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('bookTitle', 'Buku dan Kisah yang Menarik')
            ->assertSet('bookAuthors', 'Dataset Author')
            ->assertSet('bookPageCount', 222)
            ->assertSet('metadataLookupState', 'external_match')
            ->assertSee('Buku Indonesia (curated record)');

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'www.googleapis.com/books/v1/volumes'));
    }

    public function test_isbn_lookup_falls_back_to_google_books_if_indonesian_dataset_has_no_isbn(): void
    {
        Http::fake([
            'khrlanamm.github.io/buku-indonesia/api/v1/isbn/9781234567890.json' => Http::response([], 404),
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [['volumeInfo' => ['title' => 'Google Fallback Title', 'authors' => ['Google Author']]]],
            ], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('bookTitle', 'Google Fallback Title')
            ->assertSet('metadataLookupState', 'external_match');
    }

    public function test_isbn_lookup_prefers_google_books_metadata(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [[
                    'volumeInfo' => [
                        'title' => 'Google Books Title',
                        'subtitle' => 'Google Subtitle',
                        'authors' => ['Google Author'],
                        'publisher' => 'Google Publisher',
                        'pageCount' => 210,
                    ],
                ]],
            ], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('bookTitle', 'Google Books Title')
            ->assertSet('bookAuthors', 'Google Author')
            ->assertSet('bookPageCount', 210)
            ->assertSet('metadataLookupState', 'external_match');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'www.googleapis.com/books/v1/volumes')
            && $request['q'] === 'isbn:9781234567890');
    }

    public function test_isbn_lookup_falls_back_to_open_library_when_google_books_has_no_match(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response(['totalItems' => 0], 200),
            'openlibrary.org/api/books*' => Http::response([
                'ISBN:9781234567890' => [
                    'title' => 'External title',
                    'subtitle' => 'External subtitle',
                    'authors' => [['name' => 'External author']],
                    'publishers' => [['name' => 'External publisher']],
                    'number_of_pages' => 123,
                ],
            ]),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('bookTitle', 'External Title')
            ->assertSet('metadataLookupState', 'external_match');
    }


    public function test_isbn_external_provider_failure_keeps_manual_entry_available_with_reason(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'openlibrary.org/api/books*' => \Illuminate\Support\Facades\Http::response([], 503),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('metadataLookupState', 'not_found')
            ->assertSee('Open Library is temporarily unavailable.')
            ->assertSet('bookTitle', null);
    }

    public function test_isbn_lookup_reports_rate_limited_provider_with_actionable_message(): void
    {
        Http::fake([
            'openlibrary.org/api/books*' => Http::response([], 429),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('metadataLookupState', 'not_found')
            ->assertSee('rate-limiting requests');
    }

    public function test_isbn_lookup_reports_provider_connection_failure(): void
    {
        Http::fake([
            'khrlanamm.github.io/buku-indonesia/api/v1/isbn/*' => Http::response([], 404),
            'www.googleapis.com/books/v1/volumes*' => Http::response(['items' => []], 200),
            'openlibrary.org/api/books*' => Http::failedConnection(),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('metadataLookupState', 'not_found')
            ->assertSee('Open Library ISBN lookup failed');
    }

    public function test_isbn_lookup_falls_back_to_editable_empty_draft(): void
    {
        Http::fake([
            'openlibrary.org/api/books*' => Http::response([], 200),
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->call('lookupIsbn', '9781234567890')
            ->assertSet('metadataLookupState', 'not_found')
            ->assertSet('bookTitle', null)
            ->assertSet('isbnBarcodeValue', '9781234567890');
    }

    public function test_submit_manual_book_works_when_optional_lookup_timestamp_column_is_missing(): void
    {
        $this->actingAs(User::factory()->create());
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('metadata_lookup_attempted_at'));

        try {
            Livewire::test(CaptureBook::class)
                ->set('bookTitle', 'Book without optional tracking column')
                ->set('bookAuthors', 'Manual Author')
                ->call('submit')
                ->assertHasNoErrors();

            $this->assertDatabaseHas('books', ['title' => 'Book without optional tracking column']);
        } finally {
            Schema::table('books', fn (Blueprint $table) => $table->timestamp('metadata_lookup_attempted_at')->nullable());
        }
    }

    public function test_submit_manual_book_does_not_require_isbn(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->set('bookTitle', 'Manual ISBN book')
            ->set('bookAuthors', 'Manual Author')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('books', [
            'title' => 'Manual ISBN book',
            'isbn13' => null,
        ]);
    }

    public function test_submit_saves_book_with_zero_copies_by_default(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->set('bookTitle', 'Manual ISBN book')
            ->set('bookAuthors', 'Manual Author')
            ->assertSet('quantity', 0)
            ->call('submit')
            ->assertHasNoErrors();

        $book = \App\Models\Book::query()->where('title', 'Manual ISBN book')->firstOrFail();

        $this->assertSame(0, $book->copies()->count());
    }

    public function test_submit_creates_requested_copies_with_selected_intake_values(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CaptureBook::class)
            ->set('isbnBarcodeValue', '9781234567890')
            ->set('bookTitle', 'Book with copies')
            ->set('bookAuthors', 'Fixture Author')
            ->set('quantity', 2)
            ->set('fundingSource', 'BOSP')
            ->set('purchaseYear', '2026')
            ->call('submit')
            ->assertHasNoErrors();

        $book = \App\Models\Book::query()->where('title', 'Book with copies')->firstOrFail();
        $copies = $book->copies()->get();

        $this->assertCount(2, $copies);
        $this->assertSame(['BOSP (Gov-Fund)'], $copies->pluck('funding_source')->unique()->values()->all());
        $this->assertSame(['2026'], $copies->pluck('purchase_year')->unique()->values()->all());
    }

}
