<?php

namespace Tests\Feature;

use App\Filament\Resources\Books\Pages\EditBook;
use App\Filament\Resources\Books\Pages\ViewBook;
use App\Models\Book;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BookCoverDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_page_renders_existing_capture_cover_image(): void
    {
        Storage::fake('public');
        $book = Book::factory()->create(['title' => 'Cover test book']);
        $path = 'capture-sessions/cover-test/front.jpg';
        Storage::disk('public')->put($path, 'image bytes');
        $captureSession = CaptureSession::factory()->create(['front_image_path' => $path]);

        MetadataRevision::factory()->create([
            'book_id' => $book->id,
            'capture_session_id' => $captureSession->id,
            'payload' => ['front_image_path' => $path],
        ]);
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewBook::class, ['record' => $book->getRouteKey()])
            ->assertSee('src="http://localhost/public-files/'.$path.'"', false)
            ->assertSee('alt="Book cover"', false);

        $this->assertSame($path, $book->coverImagePath());
    }

    public function test_edit_page_renders_cover_and_placeholder_when_no_cover_exists(): void
    {
        Storage::fake('public');
        $book = Book::factory()->create(['title' => 'Coverless book']);
        $this->actingAs(User::factory()->create());

        Livewire::test(EditBook::class, ['record' => $book->getRouteKey()])
            ->assertSee('src="http://localhost/images/book-cover-placeholder.svg"', false)
            ->assertSee('alt="No book cover available"', false)
            ->assertSee('Upload or replace cover');
    }
}
