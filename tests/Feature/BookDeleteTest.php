<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 登録者本人は書籍を削除できる
     */
    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->delete(
                route('books.destroy', $book)
            );

        $response->assertRedirect(
            route('books.index')
        );

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );

        $this->assertDatabaseMissing(
            'books',
            [
                'id' => $book->id,
            ]
        );
    }

    /**
     * ゲストは書籍を削除できない
     */
    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $this->delete(
            route('books.destroy', $book)
        )->assertRedirect('/login');

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
            ]
        );
    }

    /**
     * 他ユーザーは書籍を削除できない
     */
    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->delete(
                route('books.destroy', $book)
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
            ]
        );
    }

    /**
     * 登録者本人には削除ボタンが表示される
     */
    public function test_delete_button_is_visible_to_owner(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($owner)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('削除');
    }

    /**
     * 他ユーザーには書籍削除フォームが表示されない
     */
    public function test_delete_button_is_not_visible_to_other_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee(
                'action="'.route('books.destroy', $book).'"',
                false
            );
    }

    /**
     * 書籍を削除すると関連レビューも削除される
     */
    public function test_reviews_are_deleted_with_book(): void
    {
        $owner = User::factory()->create();
        $reviewUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
        ]);

        $this
            ->actingAs($owner)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * 書籍を削除すると関連するお気に入りも削除される
     */
    public function test_favorites_are_deleted_with_book(): void
    {
        $owner = User::factory()->create();
        $favoriteUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $favoriteUser->favoriteBooks()
            ->attach($book->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $this
            ->actingAs($owner)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * 書籍を削除するとbook_genreの関連も削除される
     */
    public function test_book_genre_relationships_are_deleted_with_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $book->genres()
            ->attach($genre->id);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this
            ->actingAs($owner)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * 書籍を削除してもジャンル自体は削除されない
     */
    public function test_genres_are_not_deleted_with_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $book->genres()
            ->attach($genre->id);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this
            ->actingAs($owner)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}
