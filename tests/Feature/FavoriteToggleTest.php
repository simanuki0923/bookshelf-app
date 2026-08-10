<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーは書籍をお気に入り登録できる
     */
    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'お気に入りに追加しました。'
        );

        $this->assertDatabaseHas(
            'favorites',
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]
        );
    }

    /**
     * 登録済み書籍を再度操作するとお気に入り解除できる
     */
    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()
            ->attach($book->id);

        $this->assertDatabaseHas(
            'favorites',
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $response->assertSessionHas(
            'success',
            'お気に入りから解除しました。'
        );

        $this->assertDatabaseMissing(
            'favorites',
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]
        );
    }

    /**
     * ゲストはお気に入り操作できない
     */
    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::factory()->create();

        $this->post(
            route('favorites.toggle', $book)
        )->assertRedirect('/login');

        $this->assertDatabaseCount(
            'favorites',
            0
        );
    }

    /**
     * お気に入りは操作したユーザーに紐付く
     */
    public function test_favorite_is_attached_to_correct_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $book = Book::factory()->create();

        $this
            ->actingAs($user1)
            ->post(
                route('favorites.toggle', $book)
            );

        $this->assertDatabaseHas(
            'favorites',
            [
                'user_id' => $user1->id,
                'book_id' => $book->id,
            ]
        );

        $this->assertDatabaseMissing(
            'favorites',
            [
                'user_id' => $user2->id,
                'book_id' => $book->id,
            ]
        );
    }

    /**
     * 一人の解除操作が他ユーザーのお気に入りへ影響しない
     */
    public function test_removing_favorite_does_not_affect_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $book = Book::factory()->create();

        $user1->favoriteBooks()
            ->attach($book->id);

        $user2->favoriteBooks()
            ->attach($book->id);

        $this
            ->actingAs($user1)
            ->post(
                route('favorites.toggle', $book)
            );

        $this->assertDatabaseMissing(
            'favorites',
            [
                'user_id' => $user1->id,
                'book_id' => $book->id,
            ]
        );

        $this->assertDatabaseHas(
            'favorites',
            [
                'user_id' => $user2->id,
                'book_id' => $book->id,
            ]
        );
    }

    /**
     * 登録と解除を繰り返しても重複登録されない
     */
    public function test_favorite_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 1回目: 登録
        $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            );

        $this->assertDatabaseCount(
            'favorites',
            1
        );

        // 2回目: 解除
        $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            );

        $this->assertDatabaseCount(
            'favorites',
            0
        );

        // 3回目: 再登録
        $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            );

        $this->assertDatabaseCount(
            'favorites',
            1
        );
    }

    /**
     * 存在しない書籍へのお気に入り操作は404
     */
    public function test_nonexistent_book_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/books/999999/favorites')
            ->assertNotFound();

        $this->assertDatabaseCount(
            'favorites',
            0
        );
    }
}
