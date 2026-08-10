<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはお気に入り一覧を表示できない
     */
    public function test_guest_cannot_view_favorites_index(): void
    {
        $this->get(
            route('favorites.index')
        )->assertRedirect('/login');
    }

    /**
     * ログインユーザーはお気に入り一覧を表示できる
     */
    public function test_authenticated_user_can_view_favorites_index(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $response->assertViewIs(
            'favorites.index'
        );

        $response->assertViewHas(
            'books'
        );
    }

    /**
     * お気に入り登録した書籍を表示できる
     */
    public function test_favorite_book_is_displayed(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'お気に入りテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ]);

        $user->favoriteBooks()
            ->attach($book->id);

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $response->assertSee(
            'お気に入りテスト書籍'
        );

        $response->assertSee(
            'テスト著者'
        );

        $response->assertSee(
            '9781234567890'
        );
    }

    /**
     * お気に入りに登録していない書籍は表示しない
     */
    public function test_non_favorite_book_is_not_displayed(): void
    {
        $user = User::factory()->create();

        $favoriteBook = Book::factory()->create([
            'title' => '表示される書籍',
        ]);

        $nonFavoriteBook = Book::factory()->create([
            'title' => '表示されない書籍',
        ]);

        $user->favoriteBooks()
            ->attach($favoriteBook->id);

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $response->assertSee(
            '表示される書籍'
        );

        $response->assertDontSee(
            '表示されない書籍'
        );
    }

    /**
     * 他ユーザーのお気に入りは表示しない
     */
    public function test_other_users_favorites_are_not_displayed(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myBook = Book::factory()->create([
            'title' => '自分のお気に入り',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '他ユーザーのお気に入り',
        ]);

        $user->favoriteBooks()
            ->attach($myBook->id);

        $otherUser->favoriteBooks()
            ->attach($otherBook->id);

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $response->assertSee(
            '自分のお気に入り'
        );

        $response->assertDontSee(
            '他ユーザーのお気に入り'
        );
    }

    /**
     * お気に入りは1ページ10件
     */
    public function test_favorites_are_paginated_by_ten(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()
            ->count(11)
            ->create();

        $user->favoriteBooks()
            ->attach(
                $books->pluck('id')->all()
            );

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

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
     * 2ページ目を表示できる
     */
    public function test_second_page_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()
            ->count(11)
            ->create();

        $user->favoriteBooks()
            ->attach(
                $books->pluck('id')->all()
            );

        $response = $this
            ->actingAs($user)
            ->get(
                route('favorites.index', [
                    'page' => 2,
                ])
            );

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function ($books) {
                return $books->currentPage() === 2
                    && $books->count() === 1;
            }
        );
    }

    /**
     * お気に入りが0件でも一覧画面を表示できる
     */
    public function test_empty_favorites_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $response->assertSee(
            'お気に入りに登録された書籍はありません。'
        );

        $response->assertSee(
            '書籍一覧を見る'
        );
    }

    /**
     * お気に入り解除後は一覧から消える
     */
    public function test_removed_favorite_is_not_displayed(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '解除対象書籍',
        ]);

        $user->favoriteBooks()
            ->attach($book->id);

        $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            );

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $response->assertDontSee(
            '解除対象書籍'
        );
    }
}
