<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'subtitle', 'authors_display', 'isbn13', 'publisher', 'page_count', 'synopsis', 'category_id', 'approved_metadata_revision_id', 'deletion_reason'])]
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
            if ($book->isbn13) {
                \App\Jobs\FetchBookMetadataJob::dispatch($book);
            }
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
