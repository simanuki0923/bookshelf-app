<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 投稿者本人はレビュー編集画面を表示できる
     */
    public function test_owner_can_view_review_edit_page(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 4,
            'comment' => '編集前コメント',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reviews.edit', $review));

        $response->assertOk();

        $response->assertViewIs(
            'reviews.edit'
        );

        $response->assertViewHas(
            'review',
            fn ($viewReview) => $viewReview->id === $review->id
        );

        $response->assertSee(
            '編集前コメント'
        );
    }

    /**
     * ゲストはレビュー編集画面を表示できない
     */
    public function test_guest_cannot_view_review_edit_page(): void
    {
        $review = Review::factory()->create();

        $this->get(
            route('reviews.edit', $review)
        )->assertRedirect('/login');
    }

    /**
     * 他ユーザーはレビュー編集画面を表示できない
     */
    public function test_other_user_cannot_view_review_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->get(route('reviews.edit', $review))
            ->assertForbidden();
    }

    /**
     * 投稿者本人はレビューを更新できる
     */
    public function test_owner_can_update_review(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '更新後コメント',
            ]);

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '更新後コメント',
        ]);
    }

    /**
     * 更新しても対象書籍は変更されない
     */
    public function test_book_id_is_not_changed_when_review_is_updated(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 4,
                'comment' => '更新しました。',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * 更新しても投稿ユーザーは変更されない
     */
    public function test_user_id_is_not_changed_when_review_is_updated(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 4,
                'comment' => '更新しました。',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * 評価は必須
     */
    public function test_rating_is_required(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => '',
                'comment' => 'コメント',
            ])
            ->assertSessionHasErrors('rating');
    }

    /**
     * 評価は1以上5以下
     */
    public function test_rating_must_be_between_one_and_five(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 6,
                'comment' => '不正評価',
            ])
            ->assertSessionHasErrors('rating');
    }

    /**
     * コメントは空欄でも更新できる
     */
    public function test_comment_is_optional(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'comment' => '削除予定コメント',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 4,
                'comment' => '',
            ]);

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $review->refresh();

        $this->assertNull(
            $review->comment
        );
    }

    /**
     * 他ユーザーは直接PUTしても更新できない
     */
    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'rating' => 3,
            'comment' => '元コメント',
        ]);

        $this
            ->actingAs($otherUser)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '不正更新',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元コメント',
        ]);
    }

    /**
     * 存在しないレビューは404
     */
    public function test_nonexistent_review_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/reviews/999999/edit')
            ->assertNotFound();
    }
}
