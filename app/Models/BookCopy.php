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

#[Fillable(['book_id', 'tracking_code', 'qr_payload', 'status', 'location_note', 'acquired_at', 'deletion_reason'])]
class BookCopy extends Model
{
    /** @use HasFactory<BookCopyFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => BookCopyStatus::Draft->value,
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
     * Get the funding source of the book copy from the capture metadata.
     */
    public function getFundingSourceAttribute(): string
    {
        if (! $this->book_id) {
            return 'Self-Fund';
        }

        $revision = $this->book->metadataRevisions()
            ->where('source_stage', 'capture_page')
            ->latest()
            ->first();

        $source = $revision?->payload['funding_source'] ?? 'self';

        return $source === 'BOS' ? 'BOS (Gov-Fund)' : 'Self-Fund';
    }

    /**
     * Get the purchase year of the book copy from the capture metadata.
     */
    public function getPurchaseYearAttribute(): string
    {
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
