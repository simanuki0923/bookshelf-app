<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Genre>
 */
class GenreFactory extends Factory
{
    public function definition(): array
    {
        return [
            // テスト用の重複しないジャンル名
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
