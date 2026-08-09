<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストでも書籍一覧を表示できる
     */
    public function test_guest_can_view_book_index(): void
    {
        $response = $this->get(
            route('books.index')
        );

        $response->assertOk();

        $response->assertViewIs(
            'books.index'
        );

        $response->assertViewHas(
            'books'
        );
    }

    /**
     * 登録された書籍を一覧に表示できる
     */
    public function test_books_are_displayed(): void
    {
        $book = Book::factory()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $response = $this->get(
            route('books.index')
        );

        $response->assertOk();

        $response->assertSee(
            'テスト書籍'
        );

        $response->assertSee(
            'テスト著者'
        );
    }

    /**
     * 書籍を新しい順に表示する
     */
    public function test_books_are_ordered_by_latest(): void
    {
        $oldBook = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->get(
            route('books.index')
        );

        $response->assertViewHas(
            'books',
            function ($books) use (
                $oldBook,
                $newBook
            ) {
                return $books->first()->id === $newBook->id
                    && $books->last()->id === $oldBook->id;
            }
        );
    }

    /**
     * 1ページ10件でページネーションする
     */
    public function test_books_are_paginated_by_ten(): void
    {
        Book::factory()
            ->count(11)
            ->create();

        $response = $this->get(
            route('books.index')
        );

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function ($books) {
                return $books->count() === 10
                    && $books->total() === 11
                    && $books->perPage() === 10;
            }
        );
    }

    /**
     * 書籍に設定されたジャンルを表示できる
     */
    public function test_book_genres_are_loaded(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book->genres()->attach(
            $genre->id
        );

        $response = $this->get(
            route('books.index')
        );

        $response->assertOk();

        $response->assertSee(
            '技術書'
        );

        $response->assertViewHas(
            'books',
            function ($books) {
                return $books
                    ->first()
                    ->relationLoaded('genres');
            }
        );
    }

    /**
     * レビュー平均評価を取得できる
     */
    public function test_book_average_rating_is_loaded(): void
    {
        $book = Book::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user2->id,
            'rating' => 3,
        ]);

        $response = $this->get(
            route('books.index')
        );

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function ($books) {
                return (float) $books
                    ->first()
                    ->reviews_avg_rating === 4.0;
            }
        );
    }

    /**
     * 書籍がない場合でも一覧画面を表示できる
     */
    public function test_empty_book_index_can_be_displayed(): void
    {
        $response = $this->get(
            route('books.index')
        );

        $response->assertOk();

        $response->assertSee(
            '書籍が登録されていません。'
        );
    }
}
