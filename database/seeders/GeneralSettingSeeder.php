<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\GeneralSetting::updateOrCreate(
            ['id' => 1],
            [
                'library_name' => 'Perpustakaan SD Islam Insan Taqwa',
                'default_loan_duration_days' => 7,
                'max_books_per_borrower' => 3,
                'fine_per_day' => 0,
            ]
        );
    }
}
