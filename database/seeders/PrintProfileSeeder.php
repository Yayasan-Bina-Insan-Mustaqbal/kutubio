<?php

namespace Database\Seeders;

use App\Models\PrintProfile;
use Illuminate\Database\Seeder;

class PrintProfileSeeder extends Seeder
{
    public function run(): void
    {
        PrintProfile::updateOrCreate(
            ['name' => 'Default A4 Stickers (70x37mm)'],
            [
                'page_width_mm' => 210,
                'page_height_mm' => 297,
                'grid_columns' => 3,
                'grid_rows' => 8,
                'offset_x_mm' => 0,
                'offset_y_mm' => 0,
                'slot_width_mm' => 70,
                'slot_height_mm' => 37,
                'is_default' => true,
            ]
        );
    }
}
