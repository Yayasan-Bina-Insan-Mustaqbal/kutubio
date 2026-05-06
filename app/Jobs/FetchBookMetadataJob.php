<?php

namespace App\Jobs;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchBookMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Book $book,
        public string $provider = 'open_library'
    ) {}

    public function handle(): void
    {
        $isbn = $this->book->isbn13;

        if (empty($isbn)) {
            Log::info("FetchBookMetadataJob: No ISBN for book {$this->book->id}");
            return;
        }

        Log::info("FetchBookMetadataJob: Fetching metadata for ISBN {$isbn} using {$this->provider}");

        try {
            if ($this->provider === 'open_library') {
                $this->fetchFromOpenLibrary($isbn);
            } elseif ($this->provider === 'google_books') {
                $this->fetchFromGoogleBooks($isbn);
            }
        } catch (\Exception $e) {
            Log::error("FetchBookMetadataJob: Error fetching metadata for ISBN {$isbn}: " . $e->getMessage());
        }
    }

    protected function fetchFromOpenLibrary(string $isbn): void
    {
        $response = Http::timeout(10)
            ->withUserAgent('KutubioLibrary/1.0 (contact@example.com)')
            ->get("https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data");

        if ($response->failed()) {
            Log::error("FetchBookMetadataJob: Open Library API request failed for ISBN {$isbn}");
            return;
        }

        $data = $response->json();
        $bookKey = "ISBN:{$isbn}";

        if (empty($data[$bookKey])) {
            Log::warning("FetchBookMetadataJob: No data found in Open Library for ISBN {$isbn}");
            return;
        }

        $metadata = $data[$bookKey];
        $updates = [];

        if (empty($this->book->title) && !empty($metadata['title'])) {
            $updates['title'] = $metadata['title'];
        }

        if (empty($this->book->authors_display) && !empty($metadata['authors'])) {
            $updates['authors_display'] = collect($metadata['authors'])->pluck('name')->implode(', ');
        }

        if (empty($this->book->publisher) && !empty($metadata['publishers'])) {
            $updates['publisher'] = collect($metadata['publishers'])->pluck('name')->first();
        }

        if (empty($this->book->page_count) && !empty($metadata['number_of_pages'])) {
            $updates['page_count'] = $metadata['number_of_pages'];
        }

        if (empty($this->book->subtitle) && !empty($metadata['subtitle'])) {
            $updates['subtitle'] = $metadata['subtitle'];
        }

        if (!empty($updates)) {
            $this->book->update($updates);
            Log::info("FetchBookMetadataJob: Updated book {$this->book->id} with metadata from Open Library");
        }
    }

    protected function fetchFromGoogleBooks(string $isbn): void
    {
        $response = Http::timeout(10)
            ->get("https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}");

        if ($response->failed()) {
            Log::error("FetchBookMetadataJob: Google Books API request failed for ISBN {$isbn}");
            return;
        }

        $data = $response->json();

        if (empty($data['items'])) {
            Log::warning("FetchBookMetadataJob: No data found in Google Books for ISBN {$isbn}");
            return;
        }

        $volumeInfo = $data['items'][0]['volumeInfo'];
        $updates = [];

        if (empty($this->book->title) && !empty($volumeInfo['title'])) {
            $updates['title'] = $volumeInfo['title'];
        }

        if (empty($this->book->authors_display) && !empty($volumeInfo['authors'])) {
            $updates['authors_display'] = implode(', ', $volumeInfo['authors']);
        }

        if (empty($this->book->publisher) && !empty($volumeInfo['publisher'])) {
            $updates['publisher'] = $volumeInfo['publisher'];
        }

        if (empty($this->book->page_count) && !empty($volumeInfo['pageCount'])) {
            $updates['page_count'] = $volumeInfo['pageCount'];
        }

        if (empty($this->book->subtitle) && !empty($volumeInfo['subtitle'])) {
            $updates['subtitle'] = $volumeInfo['subtitle'];
        }

        if (empty($this->book->synopsis) && !empty($volumeInfo['description'])) {
            $updates['synopsis'] = Str::limit($volumeInfo['description'], 1000);
        }

        if (!empty($updates)) {
            $this->book->update($updates);
            Log::info("FetchBookMetadataJob: Updated book {$this->book->id} with metadata from Google Books");
        }
    }
}
