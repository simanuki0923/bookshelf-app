<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーはレビューにいいねできる
     */
    public function test_authenticated_user_can_like_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            );

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $response->assertSessionHas(
            'success',
            'レビューにいいねしました。'
        );

        $this->assertDatabaseHas(
            'review_likes',
            [
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]
        );
    }

    /**
     * ログインユーザーは自分自身のレビューにもいいねできる
     */
    public function test_user_can_like_own_review(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            );

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $response->assertSessionHas(
            'success',
            'レビューにいいねしました。'
        );

        $this->assertDatabaseHas(
            'review_likes',
            [
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]
        );
    }

    /**
     * いいね済みレビューを再操作すると解除できる
     */
    public function test_authenticated_user_can_unlike_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $user->likedReviews()
            ->attach($review->id);

        $this->assertDatabaseHas(
            'review_likes',
            [
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            );

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $response->assertSessionHas(
            'success',
            'レビューのいいねを解除しました。'
        );

        $this->assertDatabaseMissing(
            'review_likes',
            [
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]
        );
    }

    /**
     * ゲストはレビューにいいねできない
     */
    public function test_guest_cannot_like_review(): void
    {
        $review = Review::factory()->create();

        $this->post(
            route('reviews.like', $review)
        )->assertRedirect('/login');

        $this->assertDatabaseCount(
            'review_likes',
            0
        );
    }

    /**
     * いいねは操作したユーザーに紐付く
     */
    public function test_like_is_attached_to_correct_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $review = Review::factory()->create();

        $this
            ->actingAs($user1)
            ->post(
                route('reviews.like', $review)
            );

        $this->assertDatabaseHas(
            'review_likes',
            [
                'user_id' => $user1->id,
                'review_id' => $review->id,
            ]
        );

        $this->assertDatabaseMissing(
            'review_likes',
            [
                'user_id' => $user2->id,
                'review_id' => $review->id,
            ]
        );
    }

    /**
     * 一人のいいね解除が他ユーザーのいいねに影響しない
     */
    public function test_unlike_does_not_affect_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $review = Review::factory()->create();

        $user1->likedReviews()
            ->attach($review->id);

        $user2->likedReviews()
            ->attach($review->id);

        $this
            ->actingAs($user1)
            ->post(
                route('reviews.like', $review)
            );

        $this->assertDatabaseMissing(
            'review_likes',
            [
                'user_id' => $user1->id,
                'review_id' => $review->id,
            ]
        );

        $this->assertDatabaseHas(
            'review_likes',
            [
                'user_id' => $user2->id,
                'review_id' => $review->id,
            ]
        );
    }

    /**
     * 登録と解除を繰り返しても重複しない
     */
    public function test_like_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        // 1回目: いいね
        $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            );

        $this->assertDatabaseCount(
            'review_likes',
            1
        );

        // 2回目: 解除
        $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            );

        $this->assertDatabaseCount(
            'review_likes',
            0
        );

        // 3回目: 再びいいね
        $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            );

        $this->assertDatabaseCount(
            'review_likes',
            1
        );
    }

    /**
     * 存在しないレビューへのいいねは404
     */
    public function test_nonexistent_review_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/reviews/999999/like')
            ->assertNotFound();

        $this->assertDatabaseCount(
            'review_likes',
            0
        );
    }
}
