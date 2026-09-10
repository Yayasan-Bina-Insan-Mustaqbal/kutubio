<?php

namespace App\Models;

use App\Enums\BorrowerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Borrower extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
            'class',
            'identifier',
        'surreal_id',
        'user_id',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Borrower $borrower): void {
            $borrower->public_id ??= (string) Str::ulid();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<BorrowerEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(BorrowerEnrollment::class);
    }

    /**
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    protected function casts(): array
    {
        return [
            'type' => BorrowerType::class,
        ];
    }
}
