<?php

namespace App\Models;

use Database\Factories\PrintProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'page_width_mm', 'page_height_mm', 'grid_columns', 'grid_rows', 'offset_x_mm', 'offset_y_mm', 'slot_width_mm', 'slot_height_mm', 'gap_x_mm', 'gap_y_mm', 'is_default'])]
class PrintProfile extends Model
{
    /** @use HasFactory<PrintProfileFactory> */
    use HasFactory;

    protected $attributes = [
        'is_default' => false,
        'offset_x_mm' => 0,
        'offset_y_mm' => 0,
        'gap_x_mm' => 0,
        'gap_y_mm' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grid_columns' => 'integer',
            'grid_rows' => 'integer',
            'is_default' => 'boolean',
            'offset_x_mm' => 'decimal:2',
            'offset_y_mm' => 'decimal:2',
            'page_height_mm' => 'decimal:2',
            'page_width_mm' => 'decimal:2',
            'slot_height_mm' => 'decimal:2',
            'slot_width_mm' => 'decimal:2',
            'gap_x_mm' => 'decimal:2',
            'gap_y_mm' => 'decimal:2',
        ];
    }
}
