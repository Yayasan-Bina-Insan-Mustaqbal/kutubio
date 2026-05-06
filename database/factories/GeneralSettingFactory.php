<?php

namespace Database\Factories;

use App\Models\GeneralSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeneralSetting>
 */
class GeneralSettingFactory extends Factory
{
    protected $model = GeneralSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'library_name' => $this->faker->company(),
            'library_address' => $this->faker->address(),
            'library_employee_name' => $this->faker->name(),
            'default_loan_duration_days' => 7,
            'max_books_per_borrower' => 3,
            'fine_per_day' => 0.00,
        ];
    }
}
