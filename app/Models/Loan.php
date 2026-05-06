<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'book_copy_id',
        'borrower_id',
        'loaned_at',
        'due_at',
        'returned_at',
        'status',
        'notes',
        'deletion_reason',
    ];

    protected static function booted(): void
    {
        static::creating(function (Loan $loan): void {
            $loan->public_id ??= (string) Str::ulid();
        });
    }

    /**
     * @return BelongsTo<BookCopy, $this>
     */
    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    /**
     * @return BelongsTo<Borrower, $this>
     */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    protected function casts(): array
    {
        return [
            'loaned_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'status' => LoanStatus::class,
        ];
    }
}
