<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_books_relation_is_has_many(): void
    {
        $this->assertInstanceOf(
            HasMany::class,
            (new User)->books()
        );
    }

    public function test_reviews_relation_is_has_many(): void
    {
        $this->assertInstanceOf(
            HasMany::class,
            (new User)->reviews()
        );
    }

    public function test_favorite_books_relation_is_belongs_to_many(): void
    {
        $this->assertInstanceOf(
            BelongsToMany::class,
            (new User)->favoriteBooks()
        );
    }

    public function test_liked_reviews_relation_is_belongs_to_many(): void
    {
        $this->assertInstanceOf(
            BelongsToMany::class,
            (new User)->likedReviews()
        );
    }
}
