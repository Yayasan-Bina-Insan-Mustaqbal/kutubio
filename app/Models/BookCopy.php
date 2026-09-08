<?php

namespace App\Models;

use App\Enums\BookCopyStatus;
use Database\Factories\BookCopyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['book_id', 'tracking_code', 'qr_payload', 'status', 'funding_source', 'purchase_year', 'location_note', 'acquired_at', 'deletion_reason'])]
class BookCopy extends Model
{
    /** @use HasFactory<BookCopyFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => BookCopyStatus::Draft->value,
        'funding_source' => 'self',
        'purchase_year' => 'Old Collection',
    ];

    protected static function booted(): void
    {
        static::creating(function (BookCopy $bookCopy): void {
            $bookCopy->public_id ??= (string) Str::ulid();
            $bookCopy->qr_payload ??= "kutubio:copy:v1:{$bookCopy->public_id}";
        });
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the funding source of the book copy.
     *
     * Prefers the column value; falls back to the capture metadata for legacy copies.
     */
    public function getFundingSourceAttribute(): string
    {
        if (filled($this->attributes['funding_source'] ?? null)) {
            return $this->attributes['funding_source'] === 'BOS'
                ? 'BOSP'
                : $this->attributes['funding_source'];
        }

        if (! $this->book_id) {
            return 'self';
        }

        $revision = $this->book->metadataRevisions()
            ->where('source_stage', 'capture_page')
            ->latest()
            ->first();

        $source = $revision?->payload['funding_source'] ?? 'self';

        return $source === 'BOS' ? 'BOSP' : $source;
    }

    public function getFundingSourceLabelAttribute(): string
    {
        return $this->funding_source === 'BOSP' ? 'BOSP (Gov-Fund)' : 'Self-Fund';
    }

    /**
     * Get the purchase year of the book copy.
     *
     * Prefers the column value; falls back to the capture metadata for legacy copies.
     */
    public function getPurchaseYearAttribute(): string
    {
        if (filled($this->attributes['purchase_year'] ?? null)) {
            return $this->attributes['purchase_year'];
        }

        if (! $this->book_id) {
            return 'Old Collection';
        }

        $revision = $this->book->metadataRevisions()
            ->where('source_stage', 'capture_page')
            ->latest()
            ->first();

        return $revision?->payload['purchase_year'] ?? 'Old Collection';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'status' => BookCopyStatus::class,
        ];
    }
}
