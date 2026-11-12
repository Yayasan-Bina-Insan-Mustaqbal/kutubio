<?php

namespace App\Filament\Pages;

use App\Enums\BookCopyStatus;
use App\Filament\Resources\Books\BookResource;
use App\Models\Book;
use App\Models\Category;
use App\Services\BookCoverStorage;
use Exception;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;
use BackedEnum;

class CaptureBook extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.capture-book';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Intake';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Capture Book';

    protected static ?string $title = 'Capture Book';

    public ?string $isbnBarcodeValue = null;

    public ?string $bookTitle = null;

    public ?string $bookSubtitle = null;

    public ?string $bookAuthors = null;

    public string $authorNameStyle = 'local';

    public ?int $categoryId = null;

    public ?string $categorySearch = null;

    public ?string $bookPublisher = null;

    public ?int $bookPageCount = null;

    public ?int $matchedBookId = null;

    public mixed $coverDraft = null;

    public ?string $coverDraftPreviewUrl = null;

    public ?string $matchedBookCoverUrl = null;

    public bool $coverPanelVisible = false;

    public string $metadataLookupState = 'idle';

    public string $metadataLookupMessage = '';

    public int $quantity = 0;

    public string $fundingSource = 'self';

    public string $purchaseYear = 'Old Collection';

    public function mount(): void
    {
    }

    public function getCategoryOptionsProperty(): array
    {
        $search = trim((string) $this->categorySearch);

        return Category::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($categoryQuery) use ($search): void {
                    $categoryQuery
                        ->where('label', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('short_label', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code', 'label'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'label' => "{$category->code} — {$category->label}",
            ])
            ->all();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->categorySearch = null;
    }

    public function updatedCategorySearch(): void
    {
        $this->categoryId = null;
    }

    public function updatedIsbnBarcodeValue(?string $isbn): void
    {
        if (filled($isbn) && preg_match('/^\d{13}$/', preg_replace('/\D+/', '', $isbn)) === 1) {
            $this->lookupIsbn($isbn);
        }
    }

    #[On('capture-isbn-scanned')]
    public function handleScannedIsbn(string $isbn): void
    {
        $this->lookupIsbn($isbn);
    }

    public function lookupIsbn(?string $isbn = null): void
    {
        $isbn = preg_replace('/\D+/', '', (string) ($isbn ?? $this->isbnBarcodeValue));
        $this->isbnBarcodeValue = $isbn ?: null;
        $this->matchedBookId = null;
        $this->matchedBookCoverUrl = null;
        $this->coverPanelVisible = false;
        $this->coverDraft = null;
        $this->coverDraftPreviewUrl = null;
        $this->bookTitle = null;
        $this->bookSubtitle = null;
        $this->bookAuthors = null;
        $this->authorNameStyle = 'local';
        $this->categoryId = null;
        $this->categorySearch = null;
        $this->bookPublisher = null;
        $this->bookPageCount = null;
        $this->metadataLookupMessage = '';

        if (preg_match('/^\d{13}$/', $isbn) !== 1) {
            $this->coverPanelVisible = false;
            $this->matchedBookCoverUrl = null;
            $this->coverDraft = null;
            $this->coverDraftPreviewUrl = null;
            $this->dispatch('cover-panel-reset');
            $this->metadataLookupState = 'not_found';
            $this->metadataLookupMessage = 'Enter a valid 13-digit ISBN, or leave it blank to enter the book manually.';

            return;
        }

        $this->coverDraft = null;
        $this->matchedBookCoverUrl = asset('images/book-cover-placeholder.svg');
        $this->coverPanelVisible = true;
        $this->dispatch('cover-panel-ready');
        $this->metadataLookupState = 'looking_up';
        $this->metadataLookupMessage = 'Searching the local catalog…';

        try {
            $book = Book::query()->where('isbn13', $isbn)->first();
        } catch (Exception $exception) {
            Log::error('Local ISBN lookup failed during capture.', ['isbn' => $isbn, 'message' => $exception->getMessage()]);
            $this->matchedBookCoverUrl = asset('images/book-cover-placeholder.svg');
            $this->coverPanelVisible = true;
            $this->dispatch('cover-panel-ready');
            $this->metadataLookupState = 'not_found';
            $this->metadataLookupMessage = 'Local catalog lookup failed. You can still enter the book details manually.';

            return;
        }

        if ($book) {
            $this->matchedBookId = $book->id;
            $coverPath = $book->coverImagePath();
            $this->matchedBookCoverUrl = $coverPath
                ? route('storage.public', ['path' => $coverPath])
                : asset('images/book-cover-placeholder.svg');
            $this->fillBibliographicDraft(array_merge($book->toArray(), ['metadata_source' => 'local']));
            $this->metadataLookupState = 'local_match';
            $this->metadataLookupMessage = 'Book found in the local catalog. Review or edit the details and add copies.';

            return;
        }

        $perpusnasMetadata = $this->fetchPerpusnasMetadata($isbn);

        if ($perpusnasMetadata !== null) {
            $perpusnasMetadata['metadata_source'] = 'provider';
            $perpusnasMetadata['title'] = $this->normalizeImportedTitle($perpusnasMetadata['title'] ?? null);
            $perpusnasMetadata['subtitle'] = $this->normalizeImportedTitle($perpusnasMetadata['subtitle'] ?? null);
            $this->fillBibliographicDraft($perpusnasMetadata);
            $this->metadataLookupState = 'external_match';
            $this->metadataLookupMessage = 'Metadata found in Perpusnas ISBN. Review or edit before saving.';

            return;
        }

        $this->metadataLookupMessage = 'No Perpusnas match. Searching the Indonesian dataset…';

        try {
            $indonesianBookResponse = Http::acceptJson()
                ->withUserAgent('KutubioLibrary/1.0')
                ->connectTimeout(config('services.buku_indonesia.connect_timeout', 2))
                ->timeout(config('services.buku_indonesia.timeout', 4))
                ->get(rtrim(config('services.buku_indonesia.base_url'), '/').'/isbn/'.$isbn.'.json');

            if ($indonesianBookResponse->successful()) {
                $indonesianBook = $indonesianBookResponse->json();

                if (is_array($indonesianBook) && filled($indonesianBook['title'] ?? null)) {
                    $this->fillBibliographicDraft([
                        'metadata_source' => 'provider',
                        'title' => $indonesianBook['title'],
                        'subtitle' => $indonesianBook['subtitle'] ?? null,
                        'authors_display' => implode(', ', $indonesianBook['authors'] ?? []),
                        'publisher' => $indonesianBook['publisher'] ?? null,
                        'page_count' => $indonesianBook['pages'] ?? null,
                    ]);
                    $this->metadataLookupState = 'external_match';
                    $reviewNote = ($indonesianBook['reviewed'] ?? false) ? 'curated record' : 'unverified record';
                    $this->metadataLookupMessage = "Metadata found in Buku Indonesia ({$reviewNote}). Review or edit before saving.";

                    return;
                }
            }
        } catch (Exception $exception) {
            Log::info('Buku Indonesia ISBN lookup unavailable; continuing to Google Books.', [
                'isbn' => $isbn,
                'exception' => $exception::class,
            ]);
        }

        $this->metadataLookupMessage = 'Buku Indonesia had no match. Trying Google Books…';
        $googleLookupNotice = '';
        $googleResponse = null;

        try {
            $googleResponse = Http::acceptJson()
                ->withUserAgent('KutubioLibrary/1.0')
                ->connectTimeout(3)
                ->timeout((int) config('services.google_books.timeout', 5))
                ->retry(1, 200, fn (Exception $exception): bool => $exception instanceof \Illuminate\Http\Client\ConnectionException, throw: false)
                ->get(config('services.google_books.url', 'https://www.googleapis.com/books/v1/volumes'), array_filter([
                    'q' => 'isbn:'.$isbn,
                    'key' => config('services.google_books.key'),
                ], fn (mixed $value): bool => filled($value)));

            $googleVolume = $googleResponse->json('items.0.volumeInfo');

            if ($googleResponse->successful() && is_array($googleVolume)) {
                $this->fillBibliographicDraft([
                    'metadata_source' => 'provider',
                    'title' => $googleVolume['title'] ?? null,
                    'subtitle' => $googleVolume['subtitle'] ?? null,
                    'authors_display' => implode(', ', $googleVolume['authors'] ?? []),
                    'publisher' => $googleVolume['publisher'] ?? null,
                    'page_count' => $googleVolume['pageCount'] ?? null,
                ]);
                $this->metadataLookupState = 'external_match';
                $this->metadataLookupMessage = 'Metadata found in Google Books. Review or edit before saving.';

                return;
            }
        } catch (Exception $exception) {
            Log::warning('Google Books ISBN lookup failed during capture.', ['isbn' => $isbn, 'message' => $exception->getMessage()]);
            $googleLookupNotice = $exception instanceof \Illuminate\Http\Client\ConnectionException
                ? 'Google Books connection failed.'
                : 'Google Books request failed.';
        }

        if ($googleResponse instanceof \Illuminate\Http\Client\Response) {
            $this->metadataLookupState = 'not_found';
            $googleLookupNotice = match (true) {
                $googleResponse->status() === 401 || $googleResponse->status() === 403 => 'Google Books API key was rejected or lacks permission. Check GOOGLE_BOOKS_API_KEY and enable Books API in Google Cloud.',
                $googleResponse->status() === 429 => 'Google Books quota is exhausted or rate-limited. Open Library fallback is being checked.',
                $googleResponse->serverError() => 'Google Books is temporarily unavailable. Open Library fallback is being checked.',
                $googleResponse->successful() => 'ISBN was not found by Google Books. Trying Open Library…',
                default => 'Google Books returned HTTP '.$googleResponse->status().'. Open Library fallback is being checked.',
            };
        }

        $this->metadataLookupMessage = trim($googleLookupNotice.' '.$this->metadataLookupMessage.' Open Library fallback: checking…');

        try {
            $response = Http::acceptJson()
                ->withUserAgent('KutubioLibrary/1.0 (school library catalog)')
                ->connectTimeout(config('services.open_library.connect_timeout', 3))
                ->timeout(config('services.open_library.timeout', 8))
                ->retry(
                    config('services.open_library.retries', 2),
                    250,
                    fn (Exception $exception): bool => $exception instanceof \Illuminate\Http\Client\ConnectionException,
                    throw: false,
                )
                ->get(rtrim(config('services.open_library.base_url', 'https://openlibrary.org'), '/').'/api/books', [
                    'bibkeys' => 'ISBN:'.$isbn,
                    'format' => 'json',
                    'jscmd' => 'data',
                ]);

            if ($response->status() === 401 || $response->status() === 403) {
            $this->metadataLookupState = 'not_found';
            $this->metadataLookupMessage = 'Open Library rejected the request (HTTP '.$response->status().'). Check server outbound access or provider availability; enter details manually.';

            return;
        }

        $metadata = $response->json('ISBN:'.$isbn);

            if ($response->successful() && is_array($metadata)) {
                $this->fillBibliographicDraft([
                    'metadata_source' => 'provider',
                    'title' => $metadata['title'] ?? null,
                    'subtitle' => $metadata['subtitle'] ?? null,
                    'authors_display' => collect($metadata['authors'] ?? [])->pluck('name')->filter()->implode(', '),
                    'publisher' => collect($metadata['publishers'] ?? [])->pluck('name')->filter()->first(),
                    'page_count' => $metadata['number_of_pages'] ?? null,
                ]);
                $this->metadataLookupState = 'external_match';
                $this->metadataLookupMessage = 'Metadata found in Open Library after Google Books fallback. Review or edit before saving.';

                return;
            }

            $this->metadataLookupState = 'not_found';
            $this->metadataLookupMessage = trim($this->metadataLookupMessage.' '.match (true) {
                $response->status() === 404 => 'ISBN was not found in Google Books or Open Library. Enter bibliographic details manually.',
                $response->status() === 429 => 'Open Library is rate-limiting requests. Enter details manually or retry shortly.',
                $response->serverError() => 'Open Library is temporarily unavailable. Enter details manually or retry.',
                $response->successful() => 'ISBN was not found in Google Books or Open Library. Enter bibliographic details manually.',
                default => 'Open Library returned HTTP '.$response->status().'. Enter bibliographic details manually.',
            });
        } catch (Exception $exception) {
            Log::warning('Open Library ISBN lookup failed during capture.', [
                'isbn' => $isbn,
                'message' => $exception->getMessage(),
            ]);

            $this->metadataLookupState = 'not_found';
            $this->metadataLookupMessage = 'Open Library ISBN lookup failed ('.$exception::class.'). Check outbound connectivity; enter details manually.';
        }
    }

    public function updatedCoverDraft(): void
    {
        try {
            $this->validate([
                'coverDraft' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->coverDraft = null;
            $this->coverDraftPreviewUrl = null;

            throw $exception;
        }

        $this->coverDraftPreviewUrl = $this->coverDraft instanceof TemporaryUploadedFile ? $this->coverDraft->temporaryUrl() : null;
        $this->dispatch('cover-draft-updated', url: $this->coverDraftPreviewUrl);
    }

    public function resetCapture(): void
    {
        $this->isbnBarcodeValue = null;
        $this->bookTitle = null;
        $this->bookSubtitle = null;
        $this->bookAuthors = null;
        $this->authorNameStyle = 'local';
        $this->categoryId = null;
        $this->categorySearch = null;
        $this->bookPublisher = null;
        $this->bookPageCount = null;
        $this->matchedBookId = null;
        $this->coverPanelVisible = false;
        $this->matchedBookCoverUrl = null;
        $this->coverDraft = null;
        $this->coverDraftPreviewUrl = null;
        $this->metadataLookupState = 'idle';
        $this->metadataLookupMessage = '';
        $this->quantity = 0;
        $this->fundingSource = 'self';
        $this->purchaseYear = (string) now()->year;
        $this->resetValidation();
        $this->dispatch('capture-reset');
        $this->dispatch('capture-isbn-reset');
        $this->dispatch('cover-panel-reset');
    }

    public function updatedQuantity(mixed $quantity): void
    {
        if (! is_numeric($quantity) || (int) $quantity < 0) {
            $this->quantity = 0;
        }
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'isbnBarcodeValue' => ['nullable', 'string', 'max:64', 'regex:/^\d{13}$/'],
            'bookTitle' => ['required', 'string', 'max:255'],
            'bookSubtitle' => ['nullable', 'string', 'max:1000'],
            'bookAuthors' => ['required', 'string', 'max:1000'],
            'authorNameStyle' => ['required', 'string', 'in:local,foreign'],
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'bookPublisher' => ['nullable', 'string', 'max:255'],
            'bookPageCount' => ['nullable', 'integer', 'min:1'],
            'coverDraft' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'quantity' => ['required', 'integer', 'min:0'],
            'fundingSource' => ['required', 'string', 'in:self,BOSP'],
            'purchaseYear' => ['required', 'string', 'in:Old Collection,2023,2024,2025,2026,2027,2028,2029,2030'],
        ]);

        $matchedBookId = $this->matchedBookId;
        $wasExistingBook = $matchedBookId !== null;
        $coverDraft = $this->coverDraft;
        $newCoverPath = null;
        $oldCoverPath = null;
        $targetBookPublicId = $matchedBookId
            ? Book::query()->findOrFail($matchedBookId)->public_id
            : (string) Str::ulid();

        if ($coverDraft) {
            $bookForCover = new Book();
            $bookForCover->public_id = $targetBookPublicId;
            if ($matchedBookId) {
                $existingBook = Book::query()->findOrFail($matchedBookId);
                $oldCoverPath = $existingBook->cover_image_path;
            }
            $newCoverPath = app(BookCoverStorage::class)->storeDraft($bookForCover, $coverDraft);
        }

        try {
            $book = DB::transaction(function () use ($validated, $matchedBookId, $newCoverPath, $targetBookPublicId): Book {
                $book = $matchedBookId
                    ? Book::query()->lockForUpdate()->findOrFail($matchedBookId)
                    : ($validated['isbnBarcodeValue']
                        ? Book::query()->where('isbn13', $validated['isbnBarcodeValue'])->lockForUpdate()->first()
                        : null);

            $book ??= new Book();
            if (! $book->exists) {
                $book->public_id = $targetBookPublicId;
            }
            $book->isbn13 = $validated['isbnBarcodeValue'] ?? null;
            $book->title = $validated['bookTitle'];
            $book->subtitle = $validated['bookSubtitle'] ?? null;
            $book->authors_display = $validated['bookAuthors'];
            $book->author_name_style = $validated['authorNameStyle'];
            $book->category_id = $validated['categoryId'] ?? null;
            $book->publisher = $validated['bookPublisher'] ?? null;
                $book->page_count = $validated['bookPageCount'] ?? null;
                if ($book->getConnection()->getSchemaBuilder()->hasColumn($book->getTable(), 'metadata_lookup_attempted_at')) {
                    $book->metadata_lookup_attempted_at = now();
                }
                if ($newCoverPath) {
                    $book->cover_image_path = $newCoverPath;
                }
                $book->save();

                for ($copyNumber = 0; $copyNumber < (int) $validated['quantity']; $copyNumber++) {
                    $book->copies()->create([
                        'funding_source' => $validated['fundingSource'],
                        'purchase_year' => $validated['purchaseYear'],
                        'status' => BookCopyStatus::Draft,
                        'acquired_at' => now(),
                    ]);
                }

                return $book;
            });
        } catch (\Throwable $exception) {
            if ($newCoverPath) {
                app(BookCoverStorage::class)->remove($newCoverPath);
            }

            throw $exception;
        }

        if ($newCoverPath && filled($oldCoverPath) && str_starts_with($oldCoverPath, 'book-covers/')) {
            app(BookCoverStorage::class)->remove($oldCoverPath);
        }

        Notification::make()
            ->title($wasExistingBook ? 'Book updated' : 'Book saved')
            ->body(match (true) {
                $validated['quantity'] > 0 => "Added {$validated['quantity']} copies.",
                $wasExistingBook => 'Bibliographic information updated. No copies were added.',
                default => 'Book added to the catalog. No copies were created.',
            })
            ->success()
            ->send();

        $this->coverDraft = null;
        $this->coverDraftPreviewUrl = null;
        $this->redirect(BookResource::getUrl('edit', ['record' => $book]));
    }

    private function fetchPerpusnasMetadata(string $isbn): ?array
    {
        try {
            $response = Http::acceptJson()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->connectTimeout(3)
                ->timeout(5)
                ->get('https://isbn.perpusnas.go.id/landing_page/serverside_search2', [
                    'search' => $isbn,
                    'filter_by' => 'code',
                    'start' => 0,
                    'length' => 10,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $rows = $response->json('data');
            $data = is_array($rows) ? ($rows[0] ?? null) : null;

            if (! is_array($data) || ! filled(strip_tags((string) ($data['title'] ?? '')))) {
                return null;
            }

            $authorsDisplay = $this->normalizePerpusnasAuthors((string) ($data['kepeng'] ?? ''));
            $publishedYear = trim(strip_tags((string) ($data['tahun_terbit'] ?? '')));

            return [
                'isbn13' => $this->normalizeIsbn($isbn),
                'title' => (string) ($data['title'] ?? ''),
                'subtitle' => null,
                'authors_display' => $authorsDisplay,
                'publisher' => filled($data['nama_penerbit'] ?? null) ? trim(strip_tags((string) $data['nama_penerbit'])) : null,
                'page_count' => null,
                'published_year' => $publishedYear !== '' ? $publishedYear : null,
            ];
        } catch (\Throwable $exception) {
            Log::notice('Perpusnas ISBN endpoint unavailable; continuing with other metadata providers.', [
                'isbn' => $isbn,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    private function normalizePerpusnasAuthors(string $authorText): string
    {
        $authorText = html_entity_decode(strip_tags($authorText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $authorText = preg_replace('/\s+/u', ' ', trim($authorText)) ?? trim($authorText);
        $authorText = preg_replace('/^(?:(?:disusun|ditulis|dikarang|oleh)\s+)+/iu', '', $authorText) ?? $authorText;
        $authorText = preg_replace('/\s*[,;]?\s*(?:editor(?:s)?|edit\.?or|ilustrator|illustrator|ilustrasi|illustration|penerjemah|penerj\.?|translator|penerbit|penyunting(?:\s+buku)?|penyusun|disusun\s+oleh|ditulis\s+oleh|dengan\s+ilustrasi|illustrated\s+by)\b.*$/iu', '', $authorText) ?? $authorText;
        $authors = preg_split('/\s*[,;]\s*/u', $authorText, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(', ', array_values(array_filter(array_map(
            fn (string $author): string => trim($author, " \\t\\n\\r\\0\\x0B,;:."),
            $authors,
        ), fn (string $author): bool => $author !== '')));
    }

    private function normalizeImportedTitle(?string $title): ?string
    {
        $title = html_entity_decode(strip_tags((string) $title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('/\s+/u', ' ', trim($title)) ?? trim($title);

        if ($title === '') {
            return null;
        }

        $words = preg_split('/(\s+)/u', mb_strtolower($title, 'UTF-8'), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $lowercaseWords = ['dan', 'atau', 'tetapi', 'yang', 'di', 'ke', 'dari', 'pada', 'dengan', 'untuk', 'dalam', 'oleh', 'sebagai', 'tentang', 'terhadap', 'antara', 'hingga', 'sampai', 'karena', 'agar', 'serta', 'bagi', 'tanpa', 'atas', 'bawah', 'luar'];
        $acronyms = ['ISBN', 'DDC', 'API', 'AI', 'IPB', 'SD', 'SMP', 'SMA', 'SMK', 'TK', 'PAUD'];
        $capitalizeNext = true;

        foreach ($words as $index => $word) {
            if (trim($word) === '') {
                continue;
            }

            if (preg_match('/^\W+$/u', $word) === 1) {
                if (preg_match('/[:.!?]\s*$/u', $word) === 1) {
                    $capitalizeNext = true;
                }

                continue;
            }

            $isConjunction = in_array($word, $lowercaseWords, true);
            $isAcronym = in_array(mb_strtoupper($word, 'UTF-8'), $acronyms, true);
            $words[$index] = $isAcronym
                ? mb_strtoupper($word, 'UTF-8')
                : ($capitalizeNext || ! $isConjunction
                    ? mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($word, 1, null, 'UTF-8')
                    : $word);
            $capitalizeNext = false;
        }

        return implode('', $words);
    }

    private function normalizeIsbn(string $isbn): string
    {
        return preg_replace('/\D+/', '', $isbn) ?? '';
    }

    private function fillBibliographicDraft(array $metadata, bool $normalizeTitle = true): void
    {
        $this->isbnBarcodeValue = $metadata['isbn13'] ?? $metadata['isbn'] ?? $this->isbnBarcodeValue;
        $this->bookTitle = $normalizeTitle && ($metadata['metadata_source'] ?? null) === 'provider'
            ? $this->normalizeImportedTitle(isset($metadata['title']) ? (string) $metadata['title'] : null)
            : ($metadata['title'] ?? null);
        $this->bookSubtitle = $normalizeTitle && ($metadata['metadata_source'] ?? null) === 'provider'
            ? $this->normalizeImportedTitle(isset($metadata['subtitle']) ? (string) $metadata['subtitle'] : null)
            : ($metadata['subtitle'] ?? null);
            $this->bookAuthors = is_array($metadata['authors_display'] ?? null)
                ? implode(', ', $metadata['authors_display'])
                : ($metadata['authors_display'] ?? null);
            $this->authorNameStyle = in_array($metadata['author_name_style'] ?? null, ['local', 'foreign'], true)
                ? $metadata['author_name_style']
                : 'local';
            $this->categoryId = isset($metadata['category_id']) ? (int) $metadata['category_id'] : null;
            $this->bookPublisher = $metadata['publisher'] ?? null;
        $this->bookPageCount = isset($metadata['page_count']) ? (int) $metadata['page_count'] : null;
    }
}
