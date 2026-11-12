<?php

namespace App\Http\Controllers;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class PublicBookCatalogController extends Controller
{
    private const array SORTS = ['title_asc', 'title_desc', 'newest', 'available'];

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'available' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTS)],
        ]);

        $searchTerm = trim((string) ($filters['q'] ?? ''));
        $filters['q'] = $searchTerm;
        $suggestions = $this->searchSuggestions($searchTerm);
        $sort = $filters['sort'] ?? 'title_asc';
        $query = Book::query()
            ->select(['id', 'public_id', 'title', 'subtitle', 'authors_display', 'isbn13', 'publisher', 'page_count', 'synopsis', 'category_id', 'cover_image_path', 'created_at'])
            ->with('category:id,code,label')
            ->withCount(['copies as available_copies_count' => fn ($copies) => $copies
                ->where('status', BookCopyStatus::Available->value)]);


        if ($searchTerm !== '') {
            $query->where(function ($books) use ($searchTerm): void {
                $books->where('title', 'ilike', "%{$searchTerm}%")
                    ->orWhere('subtitle', 'ilike', "%{$searchTerm}%")
                    ->orWhere('authors_display', 'ilike', "%{$searchTerm}%")
                    ->orWhere('isbn13', 'ilike', "%{$searchTerm}%")
                    ->orWhere('publisher', 'ilike', "%{$searchTerm}%")
                    ->orWhereHas('category', fn ($category) => $category
                        ->where('label', 'ilike', "%{$searchTerm}%")
                        ->orWhere('code', 'ilike', "%{$searchTerm}%"));
            });
        }

        if (filled($filters['category'] ?? null)) {
            $query->where('category_id', $filters['category']);
        }

        if (($filters['available'] ?? false) === true || ($filters['available'] ?? null) === '1') {
            $query->whereHas('copies', fn ($copies) => $copies
                ->where('status', BookCopyStatus::Available->value));
        }

        match ($sort) {
            'title_desc' => $query->orderByDesc('title')->orderBy('id'),
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'available' => $query->orderByDesc('available_copies_count')->orderBy('title')->orderBy('id'),
            default => $query->orderBy('title')->orderBy('id'),
        };

        $books = $query->paginate(24)->withQueryString();
        $books->getCollection()->each(function (Book $book): void {
            $coverPath = $book->publicCoverImagePath();
            $book->setAttribute('public_cover_url', $coverPath
                ? route('storage.public', ['path' => $coverPath])
                : null);
        });
        $categories = Category::query()->orderBy('sort_order')->orderBy('code')->get(['id', 'code', 'label']);

        if ($request->boolean('fragment')) {
            return response()->json([
                'results' => view('catalog.partials.results', compact('books'))->render(),
                'suggestions' => $this->formatSuggestions($suggestions),
                'count' => $books->total(),
                'next' => $books->nextPageUrl(),
                'previous' => $books->previousPageUrl(),
            ]);
        }

        return view('catalog.index', [
            'books' => $books,
            'categories' => $categories,
            'filters' => array_merge(['q' => '', 'category' => '', 'available' => false, 'sort' => 'title_asc'], $filters),
            'suggestions' => $suggestions,
            'sorts' => [
                'title_asc' => 'Title A–Z',
                'title_desc' => 'Title Z–A',
                'newest' => 'Recently added',
                'available' => 'Most available',
            ],
        ]);
    }

    private function searchSuggestions(string $searchTerm): Collection
    {
        if ($searchTerm === '') {
            return collect();
        }

        return Book::query()
            ->select(['public_id', 'title', 'isbn13'])
            ->where(function ($books) use ($searchTerm): void {
                $books->where('title', 'ilike', "%{$searchTerm}%")
                    ->orWhere('subtitle', 'ilike', "%{$searchTerm}%")
                    ->orWhere('authors_display', 'ilike', "%{$searchTerm}%")
                    ->orWhere('isbn13', 'ilike', "%{$searchTerm}%")
                    ->orWhere('publisher', 'ilike', "%{$searchTerm}%")
                    ->orWhereHas('category', fn ($category) => $category
                        ->where('label', 'ilike', "%{$searchTerm}%")
                        ->orWhere('code', 'ilike', "%{$searchTerm}%"));
            })
            ->orderBy('title')
            ->limit(6)
            ->get();
    }

    private function formatSuggestions(Collection $suggestions): array
    {
        return $suggestions->map(fn (Book $book): array => [
            'title' => $book->title,
            'isbn' => $book->isbn13,
            'url' => route('catalog.books.show', $book->public_id),
        ])->all();
    }

    public function show(Book $book): View
    {
        $book->loadMissing('category:id,code,label');
        $book->loadCount(['copies as available_copies_count' => fn ($copies) => $copies
            ->where('status', BookCopyStatus::Available->value)]);
        $coverPath = $book->publicCoverImagePath();
        $book->setAttribute('public_cover_url', $coverPath
            ? route('storage.public', ['path' => $coverPath])
            : null);

        return view('catalog.show', compact('book'));
    }
}
