<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーはレビューを投稿できる
     */
    public function test_authenticated_user_can_create_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => 'とても勉強になりました。',
            ]);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても勉強になりました。',
        ]);
    }

    /**
     * ゲストはレビューを投稿できない
     */
    public function test_guest_cannot_create_review(): void
    {
        $book = Book::factory()->create();

        $this->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => 'ゲスト投稿',
            ]
        )->assertRedirect('/login');

        $this->assertDatabaseMissing(
            'reviews',
            [
                'book_id' => $book->id,
                'comment' => 'ゲスト投稿',
            ]
        );
    }

    /**
     * 評価は必須
     */
    public function test_rating_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => '',
                'comment' => 'コメントのみ',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount(
            'reviews',
            0
        );
    }

    /**
     * 評価は1以上でなければならない
     */
    public function test_rating_cannot_be_less_than_one(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 0,
                'comment' => 'テスト',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount(
            'reviews',
            0
        );
    }

    /**
     * 評価は5以下でなければならない
     */
    public function test_rating_cannot_be_greater_than_five(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 6,
                'comment' => 'テスト',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount(
            'reviews',
            0
        );
    }

    /**
     * コメントは必須
     */
    public function test_comment_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 4,
                'comment' => '',
            ]);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
        ]);
    }

    /**
     * レビューは対象書籍に紐付いて登録される
     */
    public function test_review_is_attached_to_correct_book(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('reviews.store', $book1), [
                'rating' => 5,
                'comment' => 'Book1のレビュー',
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'comment' => 'Book1のレビュー',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book2->id,
            'comment' => 'Book1のレビュー',
        ]);
    }

    /**
     * 同一ユーザーは同一書籍に複数回レビューを投稿できる
     */
    public function test_same_user_can_create_multiple_reviews_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $firstResponse = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => '1回目のレビュー',
            ]);

        $firstResponse->assertRedirect(
            route('books.show', $book)
        );

        $secondResponse = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 4,
                'comment' => '2回目のレビュー',
            ]);

        $secondResponse->assertRedirect(
            route('books.show', $book)
        );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '1回目のレビュー',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '2回目のレビュー',
        ]);

        $this->assertDatabaseCount('reviews', 2);
    }

    /**
     * 存在しない書籍にはレビューできない
     */
    public function test_review_for_nonexistent_book_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/books/999999/reviews', [
                'rating' => 5,
                'comment' => '存在しない書籍',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount(
            'reviews',
            0
        );
    }
}
