<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストでも書籍詳細を表示できる
     */
    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertOk();

        $response->assertViewIs(
            'books.show'
        );

        $response->assertViewHas(
            'book',
            fn ($viewBook) => $viewBook->id === $book->id
        );
    }

    /**
     * 書籍の基本情報を表示できる
     */
    public function test_book_information_is_displayed(): void
    {
        $book = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'description' => 'Laravelを学ぶための書籍です。',
        ]);

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertOk();

        $response->assertSee('Laravel入門');
        $response->assertSee('山田太郎');
        $response->assertSee('9781234567890');
        $response->assertSee(
            'Laravelを学ぶための書籍です。'
        );
    }

    /**
     * 書籍のジャンルを表示できる
     */
    public function test_book_genres_are_displayed(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertOk();

        $response->assertSee('技術書');

        $response->assertViewHas(
            'book',
            fn ($viewBook) => $viewBook
                ->relationLoaded('genres')
        );
    }

    /**
     * 書籍のレビューを表示できる
     */
    public function test_book_reviews_are_displayed(): void
    {
        $book = Book::factory()->create();

        $reviewer = User::factory()->create([
            'name' => 'レビュー太郎',
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'とても勉強になりました。',
        ]);

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertOk();

        $response->assertSee('レビュー太郎');
        $response->assertSee(
            'とても勉強になりました。'
        );
    }

    /**
     * レビュー関連データをまとめて取得する
     */
    public function test_review_relations_are_eager_loaded(): void
    {
        $book = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertViewHas(
            'book',
            function ($viewBook) {
                $review = $viewBook->reviews->first();

                return $viewBook->relationLoaded('reviews')
                    && $review->relationLoaded('user')
                    && $review->relationLoaded('likedByUsers');
            }
        );
    }

    /**
     * レビューを新しい順に表示する
     */
    public function test_reviews_are_ordered_by_latest(): void
    {
        $book = Book::factory()->create();

        $oldReview = Review::factory()->create([
            'book_id' => $book->id,
            'created_at' => now()->subDay(),
        ]);

        $newReview = Review::factory()->create([
            'book_id' => $book->id,
            'created_at' => now(),
        ]);

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertViewHas(
            'book',
            function ($viewBook) use (
                $oldReview,
                $newReview
            ) {
                return $viewBook->reviews->first()->id
                        === $newReview->id
                    && $viewBook->reviews->last()->id
                        === $oldReview->id;
            }
        );
    }

    /**
     * レビューがなくても詳細画面を表示できる
     */
    public function test_book_without_reviews_can_be_displayed(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(
            route('books.show', $book)
        );

        $response->assertOk();

        $response->assertSee(
            'まだレビューはありません。'
        );
    }

    /**
     * ログイン中でも書籍詳細を表示できる
     */
    public function test_authenticated_user_can_view_book_detail(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.show', $book));

        $response->assertOk();
    }

    /**
     * 存在しない書籍は404になる
     */
    public function test_nonexistent_book_returns_404(): void
    {
        $response = $this->get(
            '/books/999999'
        );

        $response->assertNotFound();
    }
}
