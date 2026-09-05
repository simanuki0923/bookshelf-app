<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    public function test_user_relation_is_belongs_to(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Review)->user()
        );
    }

    public function test_book_relation_is_belongs_to(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Review)->book()
        );
    }

    public function test_liked_by_users_relation_is_belongs_to_many(): void
    {
        $this->assertInstanceOf(
            BelongsToMany::class,
            (new Review)->likedByUsers()
        );
    }

    public function test_rating_is_cast_to_integer(): void
    {
        $review = new Review([
            'rating' => '5',
        ]);

        $this->assertSame(
            5,
            $review->rating
        );
    }
}
