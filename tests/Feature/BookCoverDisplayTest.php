<?php

namespace Tests\Feature;

use App\Filament\Resources\Books\Pages\EditBook;
use App\Filament\Resources\Books\Pages\ViewBook;
use App\Models\Book;
use App\Models\CaptureSession;
use App\Models\MetadataRevision;
use App\Models\User;
use App\Services\BookCoverStorage;
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

    public function test_edit_page_upload_persists_cover_path_and_replaces_old_file(): void
    {
        Storage::fake('public');
        $oldPath = 'book-covers/existing/old-cover.jpg';
        Storage::disk('public')->put($oldPath, 'old cover');
        $book = Book::factory()->create([
            'title' => 'Book with replaceable cover',
            'cover_image_path' => $oldPath,
        ]);
        $this->actingAs(User::factory()->create());

        $newPath = 'book-covers/'.$book->public_id.'/replacement.png';
        Storage::disk('public')->put($newPath, 'new cover');
        $component = Livewire::test(EditBook::class, ['record' => $book->getRouteKey()]);
        $component->fillForm(['title' => 'Book with replaceable cover']);
        $component->set('data.cover_image_path', [$newPath]);
        $component->call('save')->assertHasNoErrors()->assertNotified();

        $book->refresh();
        $this->assertStringStartsWith('book-covers/'.$book->public_id.'/', $book->cover_image_path);
        $this->assertTrue(Storage::disk('public')->exists($book->cover_image_path));
        $this->assertFalse(Storage::disk('public')->exists($oldPath));

        Livewire::test(EditBook::class, ['record' => $book->getRouteKey()])
            ->assertFormSet(['cover_image_path' => $book->cover_image_path]);
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
