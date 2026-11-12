<p class="result-summary">{{ $books->total() }} books found</p>
@if ($books->isEmpty())
    <div class="empty">No books match these search filters.</div>
@else
    <div class="grid">
        @foreach ($books as $book)
            @php($coverUrl = $book->public_cover_url)
            <a class="card" href="{{ route('catalog.books.show', $book->public_id) }}">
                <div class="cover">
                    <img src="{{ $coverUrl ?: asset('images/book-cover-placeholder.svg') }}" alt="Book cover: {{ $book->title }}" loading="lazy">
                    <span class="available-badge {{ $book->available_copies_count === 0 ? 'unavailable-badge' : '' }}">
                        {{ $book->available_copies_count }} {{ \Illuminate\Support\Str::plural('copy', $book->available_copies_count) }} available
                    </span>
                </div>
                <div class="info">
                    <h2 class="title">{{ $book->title }}</h2>
                    <p class="isbn">ISBN: {{ $book->isbn13 ?: 'Not available' }}</p>
                    <p class="category">{{ $book->category ? $book->category->code.' — '.$book->category->label : 'Uncategorized' }}</p>
                </div>
            </a>
        @endforeach
    </div>
    @if ($books->hasPages())
        <nav class="pagination" aria-label="Catalog pagination">
            @if ($books->onFirstPage())
                <span aria-disabled="true">Previous</span>
            @else
                <a href="{{ $books->previousPageUrl() }}" rel="prev">Previous</a>
            @endif
            <span>Page {{ $books->currentPage() }} of {{ $books->lastPage() }}</span>
            @if ($books->hasMorePages())
                <a href="{{ $books->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span aria-disabled="true">Next</span>
            @endif
        </nav>
    @endif
@endif
