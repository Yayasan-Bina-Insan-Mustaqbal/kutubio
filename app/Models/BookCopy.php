<?php

namespace App\Models;

use App\Enums\BookCopyStatus;
use Database\Factories\BookCopyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\SoftDeletes;

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
