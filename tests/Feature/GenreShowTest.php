<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはジャンル詳細画面を表示できない
     */
    public function test_guest_cannot_view_genre_show_page(): void
    {
        $genre = Genre::factory()->create();

        $this->get(
            route('genres.show', $genre)
        )->assertRedirect('/login');
    }

    /**
     * ログインユーザーはジャンル詳細画面を表示できる
     */
    public function test_authenticated_user_can_view_genre_show_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();

        $response->assertViewIs(
            'genres.show'
        );

        $response->assertViewHas(
            'genre',
            function ($viewGenre) use ($genre) {
                return $viewGenre->id === $genre->id;
            }
        );

        $response->assertViewHas(
            'books'
        );

        $response->assertSee(
            '技術書'
        );
    }

    /**
     * 対象ジャンルに紐付く書籍を表示できる
     */
    public function test_books_belonging_to_genre_are_displayed(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => 'テスト著者',
        ]);

        $genre->books()
            ->attach($book->id);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();

        $response->assertSee(
            'Laravel入門'
        );

        $response->assertSee(
            'テスト著者'
        );
    }

    /**
     * 対象ジャンルに紐付かない書籍は表示しない
     */
    public function test_books_not_belonging_to_genre_are_not_displayed(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $belongingBook = Book::factory()->create([
            'title' => '表示される書籍',
        ]);

        $unrelatedBook = Book::factory()->create([
            'title' => '表示されない書籍',
        ]);

        $genre->books()
            ->attach($belongingBook->id);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();

        $response->assertSee(
            '表示される書籍'
        );

        $response->assertDontSee(
            '表示されない書籍'
        );
    }

    /**
     * 別ジャンルの書籍は表示しない
     */
    public function test_books_from_other_genre_are_not_displayed(): void
    {
        $user = User::factory()->create();

        $targetGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $otherGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $targetBook = Book::factory()->create([
            'title' => '技術書のBook',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '小説のBook',
        ]);

        $targetGenre->books()
            ->attach($targetBook->id);

        $otherGenre->books()
            ->attach($otherBook->id);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $targetGenre));

        $response->assertOk();

        $response->assertSee(
            '技術書のBook'
        );

        $response->assertDontSee(
            '小説のBook'
        );
    }

    /**
     * 書籍一覧は1ページ10件
     */
    public function test_books_are_paginated_by_ten(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $books = Book::factory()
            ->count(11)
            ->create();

        $genre->books()
            ->attach(
                $books->pluck('id')->all()
            );

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function ($viewBooks) {
                return $viewBooks->count() === 10
                    && $viewBooks->total() === 11
                    && $viewBooks->perPage() === 10;
            }
        );
    }

    /**
     * 書籍一覧の2ページ目を表示できる
     */
    public function test_second_page_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $books = Book::factory()
            ->count(11)
            ->create();

        $genre->books()
            ->attach(
                $books->pluck('id')->all()
            );

        $response = $this
            ->actingAs($user)
            ->get(
                route('genres.show', [
                    'genre' => $genre,
                    'page' => 2,
                ])
            );

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function ($viewBooks) {
                return $viewBooks->currentPage() === 2
                    && $viewBooks->count() === 1
                    && $viewBooks->total() === 11;
            }
        );
    }

    /**
     * 書籍が0件のジャンルでも詳細画面を表示できる
     */
    public function test_genre_without_books_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '書籍なしジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();

        $response->assertSee(
            '書籍なしジャンル'
        );

        $response->assertSee(
            'このジャンルの書籍はまだ登録されていません。'
        );

        $response->assertViewHas(
            'books',
            function ($books) {
                return $books->isEmpty();
            }
        );
    }

    /**
     * 存在しないジャンルは404
     */
    public function test_nonexistent_genre_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/genres/999999')
            ->assertNotFound();
    }
}
