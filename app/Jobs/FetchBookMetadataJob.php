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
        public Book $book
    ) {}

    public function handle(): void
    {
        $isbn = $this->book->isbn13;

        if (empty($isbn)) {
            Log::info("FetchBookMetadataJob: No ISBN for book {$this->book->id}");
            return;
        }

        Log::info("FetchBookMetadataJob: Fetching metadata for ISBN {$isbn}");

        try {
            $response = Http::timeout(10)
                ->withUserAgent('KutubioLibrary/1.0 (contact@example.com)')
                ->get("https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data");

            if ($response->failed()) {
                Log::error("FetchBookMetadataJob: API request failed for ISBN {$isbn}");
                return;
            }

            $data = $response->json();
            $bookKey = "ISBN:{$isbn}";

            if (empty($data[$bookKey])) {
                Log::warning("FetchBookMetadataJob: No data found in Open Library for ISBN {$isbn}");
                return;
            }

            $metadata = $data[$bookKey];

            // Map data to book model
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

            // Subtitle
            if (empty($this->book->subtitle) && !empty($metadata['subtitle'])) {
                $updates['subtitle'] = $metadata['subtitle'];
            }

            if (!empty($updates)) {
                $this->book->update($updates);
                Log::info("FetchBookMetadataJob: Updated book {$this->book->id} with metadata from Open Library");
            }

        } catch (\Exception $e) {
            Log::error("FetchBookMetadataJob: Error fetching metadata for ISBN {$isbn}: " . $e->getMessage());
        }
    }
}
