<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\CaptureSession;
use App\Models\Category;
use App\Models\MetadataRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicBookCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_paginated_catalog_cards_with_public_fields(): void
    {
        $category = Category::factory()->create(['code' => '641.5', 'label' => 'Cooking']);
        $firstBook = Book::factory()->create([
            'title' => 'First catalog book',
            'isbn13' => '9781234567890',
            'category_id' => $category->id,
        ]);
        $firstBook->copies()->create(['status' => 'available']);
        $firstBook->copies()->create(['status' => 'borrowed']);
        $firstBook->copies()->create(['status' => 'draft']);
        Book::factory()->count(24)->create();

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Kutub<span>io</span>', false)
            ->assertSee('First catalog book')
            ->assertSee('9781234567890')
            ->assertSee('641.5 — Cooking')
            ->assertSee('1 copy available')
            ->assertSee(route('catalog.books.show', $firstBook->public_id))
            ->assertSee('name="q"', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="sort"', false)
            ->assertSee('name="available"', false)
            ->assertSee('id="search-suggestions"', false)
            ->assertSee('id="category-menu"', false)
            ->assertSee('availability-track', false)
            ->assertSee('Page 1 of 2');

        $this->assertSame(1, $response->viewData('books')->currentPage());
        $this->get(route('home', ['page' => 2]))->assertOk()->assertSee('Page 2 of 2');
    }

    public function test_public_catalog_search_category_filter_sort_and_available_filter(): void
    {
        $cooking = Category::factory()->create(['code' => '641.5', 'label' => 'Cooking']);
        $programming = Category::factory()->create(['code' => '005.1', 'label' => 'Programming']);
        $availableBook = Book::factory()->create([
            'title' => 'Zulu Cooking Handbook',
            'authors_display' => 'Ada Author',
            'isbn13' => '9780000000011',
            'category_id' => $cooking->id,
        ]);
        $availableBook->copies()->create(['status' => 'available']);
        $borrowedBook = Book::factory()->create([
            'title' => 'Alpha Cooking Guide',
            'authors_display' => 'Grace Writer',
            'isbn13' => '9780000000028',
            'category_id' => $cooking->id,
        ]);
        $borrowedBook->copies()->create(['status' => 'borrowed']);
        Book::factory()->create([
            'title' => 'Programming Fundamentals',
            'category_id' => $programming->id,
        ]);

        $fragment = $this->get(route('home', ['q' => 'Ada Author', 'fragment' => 1]));
        $fragment->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('suggestions.0.title', 'Zulu Cooking Handbook')
            ->assertJsonPath('suggestions.0.isbn', '9780000000011')
            ->assertJsonPath('suggestions.0.url', route('catalog.books.show', $availableBook->public_id));
        $this->assertStringContainsString('Zulu Cooking Handbook', $fragment->json('results'));
        $this->assertStringNotContainsString('Alpha Cooking Guide', $fragment->json('results'));
        $this->assertStringNotContainsString('Grace Writer', $fragment->json('results'));

        $filteredFragment = $this->get(route('home', ['q' => 'Ada Author', 'fragment' => 1, 'category' => $programming->id]));
        $filteredFragment->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('suggestions.0.title', 'Zulu Cooking Handbook');
        $this->assertStringNotContainsString('Zulu Cooking Handbook', $filteredFragment->json('results'));

        $this->get(route('home', ['q' => 'Ada Author']))
            ->assertOk()
            ->assertSee('Zulu Cooking Handbook')
            ->assertDontSee('Alpha Cooking Guide');

        $this->get(route('home', ['q' => 'Ada Author', 'available' => '1', 'sort' => 'newest', 'fragment' => 1]))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('next', null)
            ->assertJsonPath('previous', null)
            ->assertJsonPath('suggestions.0.title', 'Zulu Cooking Handbook');

        $this->get(route('home', ['category' => $cooking->id]))
            ->assertOk()
            ->assertSee('Zulu Cooking Handbook')
            ->assertSee('Alpha Cooking Guide')
            ->assertDontSee('Programming Fundamentals');

        $this->get(route('home', ['available' => '1']))
            ->assertOk()
            ->assertSee('Zulu Cooking Handbook')
            ->assertDontSee('Alpha Cooking Guide')
            ->assertDontSee('Programming Fundamentals');

        $this->get(route('home', ['sort' => 'title_desc']))
            ->assertOk()
            ->assertSeeInOrder(['Zulu Cooking Handbook', 'Programming Fundamentals', 'Alpha Cooking Guide']);
    }

    public function test_live_fragment_returns_recommendations_and_matching_cards_in_one_response(): void
    {
        $category = Category::factory()->create(['code' => '641.5', 'label' => 'Cooking']);
        $book = Book::factory()->create([
            'title' => 'Matching live result',
            'isbn13' => '9781111111111',
            'authors_display' => 'Cook Author',
            'category_id' => $category->id,
        ]);
        $book->copies()->create(['status' => 'available']);

        $response = $this->get(route('home', [
            'q' => 'Cook Author',
            'category' => $category->id,
            'available' => 1,
            'sort' => 'newest',
            'fragment' => 1,
        ]));

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('suggestions.0.url', route('catalog.books.show', $book->public_id))
            ->assertJsonPath('results', fn (string $html): bool => str_contains($html, 'Matching live result') && str_contains($html, '1 copy available'));

        $emptyResponse = $this->get(route('home', ['q' => 'Not found', 'fragment' => 1]));
        $emptyResponse->assertOk()->assertJsonPath('count', 0)->assertJsonCount(0, 'suggestions');
        $this->assertStringContainsString('No books match these search filters.', $emptyResponse->json('results'));
    }

    public function test_invalid_catalog_filter_values_are_rejected(): void
    {
        $this->get(route('home', ['sort' => 'title;drop table books']))->assertSessionHasErrors('sort');
        $this->get(route('home', ['category' => '999999']))->assertSessionHasErrors('category');
        $this->get(route('home', ['q' => str_repeat('x', 101)]))->assertSessionHasErrors('q');
    }

    public function test_guest_can_open_public_read_only_book_detail_by_public_id(): void
    {
        $category = Category::factory()->create(['code' => '005.1', 'label' => 'Programming']);
        $book = Book::factory()->create([
            'title' => 'Public detail book',
            'isbn13' => '9781234567890',
            'authors_display' => 'Ada Lovelace',
            'publisher' => 'Public Press',
            'page_count' => 321,
            'synopsis' => 'Public synopsis text',
            'category_id' => $category->id,
        ]);

        $this->get(route('catalog.books.show', $book->public_id))
            ->assertOk()
            ->assertSee('Public detail book')
            ->assertSee('Ada Lovelace')
            ->assertSee('9781234567890')
            ->assertSee('005.1 — Programming')
            ->assertSee('Public Press')
            ->assertSee('321')
            ->assertSee('Public synopsis text')
            ->assertSee('Synopsis')
            ->assertSee('Available copies')
            ->assertSee('0')
            ->assertDontSee('Edit Book')
            ->assertDontSee('copies_count')
            ->assertDontSee('tracking_code');
    }

    public function test_public_catalog_does_not_show_soft_deleted_books(): void
    {
        $book = Book::factory()->create(['title' => 'Removed catalog book']);
        $book->delete();

        $this->get(route('home'))->assertOk()->assertDontSee('Removed catalog book');
        $this->get(route('catalog.books.show', $book->public_id))->assertNotFound();
    }

    public function test_public_book_detail_uses_placeholder_when_no_cover_exists(): void
    {
        $book = Book::factory()->create(['title' => 'Book without cover']);

        $this->get(route('catalog.books.show', $book->public_id))
            ->assertOk()
            ->assertSee(asset('images/book-cover-placeholder.svg'), false)
            ->assertSee('alt="Book cover: Book without cover"', false);
    }

    public function test_public_cover_route_serves_capture_and_book_cover_images_only(): void
    {
        Storage::fake('public');
        $bookCoverPath = 'book-covers/public/cover.png';
        Storage::disk('public')->put($bookCoverPath, 'book cover bytes');

        $this->get(route('storage.public', ['path' => $bookCoverPath]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $privatePath = 'private/secret.png';
        Storage::disk('public')->put($privatePath, 'private bytes');

        $this->get(route('storage.public', ['path' => $privatePath]))->assertNotFound();
        $this->get(route('storage.public', ['path' => 'book-covers/missing.png']))->assertNotFound();
    }

    public function test_public_cover_route_does_not_serve_raw_capture_session_files(): void
    {
        Storage::fake('public');
        $rawCapturePath = 'capture-sessions/private/front.jpg';
        Storage::disk('public')->put($rawCapturePath, 'private raw capture bytes');

        $this->get(route('storage.public', ['path' => $rawCapturePath]))->assertNotFound();
    }

    public function test_public_book_detail_resolves_linked_capture_session_cover(): void
    {
        Storage::fake('public');
        $path = 'capture-sessions/public/front.jpg';
        Storage::disk('public')->put($path, 'capture bytes');
        $book = Book::factory()->create(['title' => 'Capture cover book']);
        $session = CaptureSession::factory()->create(['front_image_path' => $path]);
        MetadataRevision::factory()->create([
            'book_id' => $book->id,
            'capture_session_id' => $session->id,
            'payload' => ['front_image_path' => $path],
        ]);

        $expectedPublicCoverPath = 'book-covers/'.$book->public_id.'/capture-'.$session->id.'.jpg';

        $this->get(route('catalog.books.show', $book->public_id))
            ->assertOk()
            ->assertSee('Capture cover book')
            ->assertSee(asset('images/book-cover-placeholder.svg'), false);

        $this->assertNull($book->fresh()->cover_image_path);
    }
}
