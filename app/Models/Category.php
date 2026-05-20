<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'label', 'short_label', 'color', 'sort_order', 'source_version'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Book, $this>
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * Get theme background color according to category DDC code prefix.
     */
    public function getThemeBgColorAttribute(): string
    {
        $firstChar = substr($this->code ?? '0', 0, 1);

        return match ($firstChar) {
            '0' => '#f1f5f9', // Slate
            '1' => '#e0e7ff', // Indigo
            '2' => '#dcfce7', // Green
            '3' => '#fef3c7', // Amber
            '4' => '#ccfbf1', // Teal
            '5' => '#ecfeff', // Cyan
            '6' => '#ffe4e6', // Rose
            '7' => '#fdf4ff', // Fuchsia
            '8' => '#f5f3ff', // Purple
            '9' => '#f5f5f4', // Stone
            default => '#f3f4f6',
        };
    }

    /**
     * Get theme text color according to category DDC code prefix.
     */
    public function getThemeTextColorAttribute(): string
    {
        $firstChar = substr($this->code ?? '0', 0, 1);

        return match ($firstChar) {
            '0' => '#475569',
            '1' => '#4338ca',
            '2' => '#15803d',
            '3' => '#b45309',
            '4' => '#0f766e',
            '5' => '#0e7490',
            '6' => '#be123c',
            '7' => '#a21caf',
            '8' => '#6d28d9',
            '9' => '#57534e',
            default => '#374151',
        };
    }
}
