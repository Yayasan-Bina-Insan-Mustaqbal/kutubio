<?php

namespace App\Models;

use App\Enums\BookCopyStatus;
use Database\Factories\BookCopyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
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

    public function applyBulkUpdates(array $updates, ?BookCopyStatus $availabilityStatus = null): bool
    {
        if ($availabilityStatus !== null && $this->status !== BookCopyStatus::Borrowed) {
            $updates['status'] = $availabilityStatus;
        }

        if ($this->status === BookCopyStatus::Borrowed && isset($updates['status'])) {
            unset($updates['status']);
        }

        if ($updates === []) {
            return false;
        }

        return $this->update($updates);
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
        $fundingSource = $this->attributes['funding_source'] ?? 'self';

        return in_array($fundingSource, ['BOSP', 'BOS', 'BOSP (Gov-Fund)'], true)
            ? 'BOSP (Gov-Fund)'
            : 'Self-Fund';
    }

    public function getPurchaseYearAttribute(): string
    {
        return (string) ($this->attributes['purchase_year'] ?? 'Old Collection');
    }

    public static function uniformStoredValue(Collection $records, string $attribute): ?string
    {
        if ($records->isEmpty()) {
            return null;
        }

        $values = $records->map(function (BookCopy $record) use ($attribute): ?string {
            $storedValue = self::normalizeStoredValue($attribute, $record->getRawOriginal($attribute));

            if ($storedValue !== null) {
                return $storedValue;
            }

            if (! $record->book_id) {
                return null;
            }

            $capturePayload = $record->book->metadataRevisions()
                ->where('source_stage', 'capture_page')
                ->latest()
                ->value('payload');

            return self::normalizeStoredValue($attribute, data_get($capturePayload, $attribute));
        });

        if ($values->contains(fn (?string $value): bool => $value === null)) {
            return null;
        }

        $values = $values->unique();

        return $values->count() === 1 ? $values->first() : null;
    }

    public static function normalizeStoredValue(string $attribute, mixed $value): ?string
    {
        if ($attribute === 'funding_source' && in_array($value, ['BOS', 'BOSP', 'BOSP (Gov-Fund)'], true)) {
            return 'BOSP';
        }

        if ($attribute === 'funding_source' && in_array($value, ['self', 'Self-Fund'], true)) {
            return 'self';
        }

        if (blank($value)) {
            return null;
        }

        if ($attribute === 'funding_source') {
            return (string) $value;
        }

        if ($attribute === 'status' && $value instanceof BookCopyStatus) {
            return $value->value;
        }

        return (string) $value;
    }


    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'status' => BookCopyStatus::class,
        ];
    }
}
