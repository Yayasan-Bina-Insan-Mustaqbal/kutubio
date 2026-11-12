<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'subtitle', 'authors_display', 'author_name_style', 'isbn13', 'metadata_lookup_attempted_at', 'cover_image_path', 'publisher', 'page_count', 'synopsis', 'category_id', 'approved_metadata_revision_id', 'deletion_reason'])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Book $book): void {
            $book->public_id ??= (string) Str::ulid();
        });

        static::created(function (Book $book): void {
            if ($book->isbn13 && ! $book->metadata_lookup_attempted_at) {
                \App\Jobs\FetchBookMetadataJob::dispatch($book);
            }
        });

        static::deleted(function (Book $book): void {
            $book->copies()->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'metadata_lookup_attempted_at' => 'datetime',
        ];
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function coverImagePath(): ?string
    {
        if (filled($this->cover_image_path) && Storage::disk('public')->exists($this->cover_image_path)) {
            return $this->cover_image_path;
        }

        $captureSession = $this->metadataRevisions()
            ->whereNotNull('capture_session_id')
            ->whereHas('captureSession', fn ($query) => $query->whereNotNull('front_image_path'))
            ->with('captureSession')
            ->latest()
            ->first()?->captureSession;
        $sourcePath = $captureSession?->front_image_path;

        if (! $captureSession || ! filled($sourcePath) || ! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        if (! str_starts_with($sourcePath, 'capture-sessions/') || app()->runningUnitTests()) {
            return $sourcePath;
        }

        return $this->promoteLegacyCaptureCover($captureSession->id, $sourcePath);
    }

    private function promoteLegacyCaptureCover(int $captureSessionId, string $sourcePath): ?string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $destinationPath = 'book-covers/' . $this->public_id . '/capture-' . $captureSessionId . '.' . $extension;
        $disk = Storage::disk('public');

        if (! $disk->exists($destinationPath) && ! $disk->copy($sourcePath, $destinationPath)) {
            return null;
        }

        $disk->setVisibility($destinationPath, 'public');
        $this->forceFill(['cover_image_path' => $destinationPath])->saveQuietly();

        return $destinationPath;
    }

    public function publicCoverImagePath(): ?string
    {
        $path = $this->coverImagePath();

        return filled($path) && str_starts_with($path, 'book-covers/') ? $path : null;
    }

    public function coverImageUrl(): ?string
    {
        $path = $this->publicCoverImagePath();

        return $path ? route('storage.public', ['path' => $path]) : null;
    }

    /**
     * @return HasMany<BookCopy, $this>
     */
    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    /**
     * @return HasMany<MetadataRevision, $this>
     */
    public function metadataRevisions(): HasMany
    {
        return $this->hasMany(MetadataRevision::class);
    }

    /**
     * @return BelongsTo<MetadataRevision, $this>
     */
    public function approvedMetadataRevision(): BelongsTo
    {
        return $this->belongsTo(MetadataRevision::class, 'approved_metadata_revision_id');
    }
}
