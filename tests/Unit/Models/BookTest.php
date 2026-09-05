<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class BookTest extends TestCase
{
    public function test_user_relation_is_belongs_to(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Book)->user()
        );
    }

    public function test_genres_relation_is_belongs_to_many(): void
    {
        $this->assertInstanceOf(
            BelongsToMany::class,
            (new Book)->genres()
        );
    }

    public function test_reviews_relation_is_has_many(): void
    {
        $this->assertInstanceOf(
            HasMany::class,
            (new Book)->reviews()
        );
    }

    public function test_favorited_by_users_relation_is_belongs_to_many(): void
    {
        $this->assertInstanceOf(
            BelongsToMany::class,
            (new Book)->favoritedByUsers()
        );
    }

    public function test_published_date_is_cast_to_date(): void
    {
        $book = new Book([
            'published_date' => '2026-09-05',
        ]);

        $this->assertSame(
            '2026-09-05',
            $book->published_date->format('Y-m-d')
        );
    }
}
