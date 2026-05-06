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
use Illuminate\Support\Str;

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

        Log::info("FetchBookMetadataJob: Fetching metadata for book {$this->book->id} using {$this->provider}");

        try {
            if ($this->provider === 'open_library') {
                if (!$isbn) return;
                $this->fetchFromOpenLibrary($isbn);
            } elseif ($this->provider === 'isbn_search') {
                if (!$isbn) return;
                $this->withDelay(fn() => $this->fetchFromIsbnSearch($isbn));
            } elseif ($this->provider === 'searxng') {
                $this->withDelay(fn() => $this->fetchFromSearxNG());
            }
        } catch (\Exception $e) {
            Log::error("FetchBookMetadataJob: Error fetching metadata for book {$this->book->id}: " . $e->getMessage());
        }
    }

    protected function withDelay(callable $callback): void
    {
        $delay = rand(5, 15);
        Log::info("FetchBookMetadataJob: Delaying for {$delay} seconds to avoid rate limiting...");
        sleep($delay);
        $callback();
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

    protected function fetchFromIsbnSearch(string $isbn): void
    {
        $response = Http::timeout(15)
            ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
            ->get("https://isbnsearch.org/isbn/{$isbn}");

        if ($response->failed()) {
            Log::error("FetchBookMetadataJob: ISBN Search request failed for ISBN {$isbn}");
            return;
        }

        $html = $response->body();
        $updates = [];

        if (empty($this->book->title) && preg_match('/<h1>(.*?)<\/h1>/s', $html, $matches)) {
            $updates['title'] = trim($matches[1]);
        }

        if (empty($this->book->authors_display) && preg_match('/<strong>Author:<\/strong>\s*(.*?)\s*<\/p>/s', $html, $matches)) {
            $updates['authors_display'] = trim($matches[1]);
        }

        if (empty($this->book->publisher) && preg_match('/<strong>Publisher:<\/strong>\s*(.*?)\s*<\/p>/s', $html, $matches)) {
            $updates['publisher'] = trim($matches[1]);
        }

        if (!empty($updates)) {
            $this->book->update($updates);
            Log::info("FetchBookMetadataJob: Updated book {$this->book->id} with metadata from ISBN Search");
        }
    }

    protected function fetchFromSearxNG(): void
    {
        $query = "ISBN " . ($this->book->isbn13 ?? ($this->book->title . " " . $this->book->authors_display));
        
        Log::info("FetchBookMetadataJob: Searching SearxNG for: {$query}");

        // Using the internal docker service name 'searxng'
        $response = Http::timeout(20)
            ->get("http://searxng:8080/search", [
                'q' => $query,
                'format' => 'json',
            ]);

        if ($response->failed()) {
            Log::error("FetchBookMetadataJob: SearxNG search failed for query: {$query}");
            return;
        }

        $data = $response->json();
        $results = $data['results'] ?? [];
        
        $combinedText = "";
        foreach ($results as $result) {
            $combinedText .= ($result['title'] ?? '') . " " . ($result['content'] ?? '') . " ";
        }
        
        // Look for ISBN-13 patterns in search results if book doesn't have one
        if (empty($this->book->isbn13)) {
            if (preg_match('/978[0-9]{10}/', $combinedText, $matches)) {
                $foundIsbn = $matches[0];
                Log::info("FetchBookMetadataJob: Discovered ISBN {$foundIsbn} via SearxNG");
                $this->book->update(['isbn13' => $foundIsbn]);
                
                // Now that we have an ISBN, try structured fetching
                $this->fetchFromOpenLibrary($foundIsbn);
                return;
            }
        }

        Log::info("FetchBookMetadataJob: No actionable ISBN found in SearxNG results.");
    }
}
