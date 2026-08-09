<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            // 登録ユーザーもFactoryで生成可能
            'user_id' => User::factory(),

            'title' => fake()->sentence(4),
            'author' => fake()->name(),

            // ISBN形式の13桁文字列
            'isbn' => fake()->unique()->numerify('#############'),

            'published_date' => fake()
                ->dateTimeBetween('-100 years', 'now')
                ->format('Y-m-d'),

            'description' => fake()->paragraph(),

            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=Book',
        ];
    }
}
