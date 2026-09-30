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

    public function getFundingSourceAttribute(): string
    {
        return in_array($this->attributes['funding_source'] ?? 'self', ['BOSP', 'BOS', 'BOSP (Gov-Fund)'], true)
            ? 'BOSP (Gov-Fund)'
            : 'Self-Fund';
    }

    public function getPurchaseYearAttribute(): string
    {
        return (string) ($this->attributes['purchase_year'] ?? 'Old Collection');
    }


    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'status' => BookCopyStatus::class,
        ];
    }
}
