<?php

namespace Database\Factories;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookCopy>
 */
class BookCopyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'tracking_code' => fake()->optional()->bothify('LIB-####'),
            'status' => fake()->randomElement(BookCopyStatus::cases()),
            'location_note' => fake()->optional()->sentence(),
            'acquired_at' => fake()->optional()->dateTimeBetween('-5 years'),
        ];
    }
}
