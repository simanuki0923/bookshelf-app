<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは編集画面へアクセスできない
     */
    public function test_guest_cannot_view_book_edit_page(): void
    {
        $book = Book::factory()->create();

        $this->get(
            route('books.edit', $book)
        )->assertRedirect('/login');
    }

    /**
     * 登録者本人は編集画面を表示できる
     */
    public function test_owner_can_view_book_edit_page(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this
            ->actingAs($owner)
            ->get(route('books.edit', $book));

        $response->assertOk();

        $response->assertViewIs(
            'books.edit'
        );

        $response->assertViewHas(
            'book',
            fn ($viewBook) => $viewBook->id === $book->id
        );

        $response->assertViewHas('genres');
    }

    /**
     * 他ユーザーは編集画面へアクセスできない
     */
    public function test_other_user_cannot_view_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->get(route('books.edit', $book))
            ->assertForbidden();
    }

    /**
     * 登録者本人は書籍を更新できる
     */
    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'isbn' => '9781234567890',
        ]);

        $genre = Genre::factory()->create();

        $response = $this
            ->actingAs($owner)
            ->put(route('books.update', $book), [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'description' => '更新後の説明です。',
                'image_url' => 'https://example.com/book.jpg',
                'genres' => [$genre->id],
            ]);

        $response->assertRedirect(
            route('books.show', $book)
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $owner->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9781234567890',
        ]);
    }

    /**
     * 書籍更新時にジャンルも更新できる
     */
    public function test_genres_are_updated_with_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book->genres()->attach($oldGenre->id);

        $this
            ->actingAs($owner)
            ->put(route('books.update', $book), [
                'title' => $book->title,
                'author' => $book->author,
                'isbn' => $book->isbn,
                'published_date' => '2026-08-01',
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => [$newGenre->id],
            ]);

        $this->assertDatabaseMissing(
            'book_genre',
            [
                'book_id' => $book->id,
                'genre_id' => $oldGenre->id,
            ]
        );

        $this->assertDatabaseHas(
            'book_genre',
            [
                'book_id' => $book->id,
                'genre_id' => $newGenre->id,
            ]
        );
    }

    /**
     * 自分自身のISBNはそのまま使用できる
     */
    public function test_current_isbn_can_be_kept(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'isbn' => '9781234567890',
        ]);

        $this
            ->actingAs($owner)
            ->put(route('books.update', $book), [
                'title' => '更新タイトル',
                'author' => '更新著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'genres' => [$genre->id],
            ])
            ->assertSessionDoesntHaveErrors('isbn');
    }

    /**
     * 他書籍と同じISBNには変更できない
     */
    public function test_duplicate_isbn_cannot_be_used(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '9781111111111',
        ]);

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'isbn' => '9782222222222',
        ]);

        $this
            ->actingAs($owner)
            ->put(route('books.update', $book), [
                'title' => '更新タイトル',
                'author' => '更新著者',
                'isbn' => '9781111111111',
                'published_date' => '2026-08-01',
                'genres' => [$genre->id],
            ])
            ->assertSessionHasErrors('isbn');
    }

    /**
     * 必須項目がない場合は更新できない
     */
    public function test_required_fields_are_validated(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($owner)
            ->put(
                route('books.update', $book),
                []
            )
            ->assertSessionHasErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);
    }

    /**
     * 他ユーザーは直接PUTしても更新できない
     */
    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '元タイトル',
        ]);

        $this
            ->actingAs($otherUser)
            ->put(route('books.update', $book), [
                'title' => '不正な更新',
                'author' => '不正ユーザー',
                'isbn' => $book->isbn,
                'published_date' => '2026-08-01',
                'genres' => [$genre->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '元タイトル',
        ]);
    }
}
