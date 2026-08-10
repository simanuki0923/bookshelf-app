<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 投稿者本人はレビューを削除できる
     */
    public function test_owner_can_delete_review(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'comment' => '削除対象レビュー',
        ]);

        $response = $this
            ->actingAs($owner)
            ->delete(
                route('reviews.destroy', $review)
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );

        $this->assertDatabaseMissing(
            'reviews',
            [
                'id' => $review->id,
            ]
        );
    }

    /**
     * ゲストはレビューを削除できない
     */
    public function test_guest_cannot_delete_review(): void
    {
        $review = Review::factory()->create();

        $this->delete(
            route('reviews.destroy', $review)
        )->assertRedirect('/login');

        $this->assertDatabaseHas(
            'reviews',
            [
                'id' => $review->id,
            ]
        );
    }

    /**
     * 他ユーザーはレビューを削除できない
     */
    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'comment' => '元レビュー',
        ]);

        $this
            ->actingAs($otherUser)
            ->delete(
                route('reviews.destroy', $review)
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'reviews',
            [
                'id' => $review->id,
                'comment' => '元レビュー',
            ]
        );
    }

    /**
     * 投稿者本人には削除フォームを表示する
     */
    public function test_delete_form_is_visible_to_owner(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $this
            ->actingAs($owner)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee(
                'action="'.route('reviews.destroy', $review).'"',
                false
            );
    }

    /**
     * 他ユーザーには削除フォームを表示しない
     */
    public function test_delete_form_is_not_visible_to_other_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee(
                'action="'.route('reviews.destroy', $review).'"',
                false
            );
    }

    /**
     * レビュー削除時にレビューいいねも削除される
     */
    public function test_review_likes_are_deleted_with_review(): void
    {
        $owner = User::factory()->create();
        $liker = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        $liker->likedReviews()->attach(
            $review->id
        );

        $this->assertDatabaseHas(
            'review_likes',
            [
                'user_id' => $liker->id,
                'review_id' => $review->id,
            ]
        );

        $this
            ->actingAs($owner)
            ->delete(
                route('reviews.destroy', $review)
            );

        $this->assertDatabaseMissing(
            'review_likes',
            [
                'user_id' => $liker->id,
                'review_id' => $review->id,
            ]
        );
    }

    /**
     * 存在しないレビューは404
     */
    public function test_nonexistent_review_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->delete('/reviews/999999')
            ->assertNotFound();
    }
}
