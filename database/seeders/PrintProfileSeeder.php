<?php

namespace Database\Seeders;

use App\Models\PrintProfile;
use Illuminate\Database\Seeder;

class PrintProfileSeeder extends Seeder
{
    public function run(): void
    {
        PrintProfile::updateOrCreate(
            ['name' => 'Tom & Jerry 103 (63x31mm)'],
            [
                'page_width_mm' => 210,
                'page_height_mm' => 164,
                'grid_columns' => 3,
                'grid_rows' => 4,
                'offset_x_mm' => 3,
                'offset_y_mm' => 9,
                'slot_width_mm' => 63,
                'slot_height_mm' => 31,
                'gap_x_mm' => 4,
                'gap_y_mm' => 7,
                'is_default' => true,
            ]
        );
    }
}
