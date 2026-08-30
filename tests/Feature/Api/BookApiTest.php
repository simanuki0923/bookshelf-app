<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは書籍一覧を取得できる
     */
    public function test_guest_can_get_books(): void
    {
        Book::factory()
            ->count(3)
            ->create();

        $response = $this->getJson(
            route('api.v1.books.index')
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    /**
     * 未認証でも書籍を登録できる
     */
    public function test_book_without_authentication_can_be_created(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->postJson(
            route('api.v1.books.store'),
            [
                'user_id' => $user->id,
                'title' => 'API登録書籍',
                'author' => 'API著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-11',
                'description' => 'APIテスト',
                'image_url' => 'https://example.com/book.jpg',
                'genres' => [
                    $genre->id,
                ],
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'books',
            [
                'user_id' => $user->id,
                'title' => 'API登録書籍',
                'isbn' => '9781234567890',
                'image_url' => 'https://example.com/book.jpg',
            ]
        );
    }

    /**
     * 書籍詳細を取得できる
     */
    public function test_guest_can_get_book_detail(): void
    {
        $book = Book::factory()->create([
            'title' => 'API詳細テスト',
        ]);

        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $book->id
            )
            ->assertJsonPath(
                'data.title',
                'API詳細テスト'
            );
    }

    /**
     * 存在しない書籍の更新は404とJSONエラーを返す
     */
    public function test_updating_nonexistent_book_returns_404_with_json_error(): void
    {
        $response = $this->putJson(
            '/api/v1/books/999999',
            []
        );

        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '書籍が見つかりません。',
            ]);
    }

    /**
     * 存在しない書籍の削除は404とJSONエラーを返す
     */
    public function test_deleting_nonexistent_book_returns_404_with_json_error(): void
    {
        $response = $this->deleteJson(
            '/api/v1/books/999999'
        );

        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '書籍が見つかりません。',
            ]);
    }

    /**
     * 未認証でも書籍を更新できる
     */
    public function test_book_without_authentication_can_be_updated(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $response = $this->putJson(
            route('api.v1.books.update', $book),
            [
                'user_id' => $user->id,
                'title' => 'API更新後書籍',
                'author' => '更新後著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-12',
                'description' => '更新後',
                'image_url' => 'https://example.com/updated.jpg',
                'genres' => [
                    $genre->id,
                ],
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
                'title' => 'API更新後書籍',
                'image_url' => 'https://example.com/updated.jpg',
            ]
        );
    }

    /**
     * 未認証でも書籍を削除できる
     */
    public function test_book_without_authentication_can_be_deleted(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson(
            route('api.v1.books.destroy', $book)
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing(
            'books',
            [
                'id' => $book->id,
            ]
        );
    }

    /**
     * 書籍詳細にレビュー情報を含める
     */
    public function test_book_detail_contains_reviews(): void
    {
        $user = User::factory()->create([
            'name' => 'レビュー投稿者',
        ]);

        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'APIレビューテスト',
        ]);

        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.reviews.0.user_name',
                'レビュー投稿者'
            )
            ->assertJsonPath(
                'data.reviews.0.rating',
                5
            )
            ->assertJsonPath(
                'data.reviews.0.comment',
                'APIレビューテスト'
            );
    }

    /**
     * 書籍登録時の画像URLは255文字まで許可する
     */
    public function test_image_url_can_be_255_characters_when_creating_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $prefix = 'https://example.com/';

        $imageUrl = $prefix
            .str_repeat(
                'a',
                255 - strlen($prefix)
            );

        $this->assertSame(
            255,
            strlen($imageUrl)
        );

        $response = $this->postJson(
            route('api.v1.books.store'),
            [
                'user_id' => $user->id,
                'title' => 'API画像URL境界値テスト',
                'author' => 'API著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-11',
                'description' => '255文字URLテスト',
                'image_url' => $imageUrl,
                'genres' => [
                    $genre->id,
                ],
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'books',
            [
                'isbn' => '9781234567890',
                'image_url' => $imageUrl,
            ]
        );
    }

    /**
     * 書籍登録時の画像URLは256文字以上の場合登録できない
     */
    public function test_image_url_cannot_exceed_255_characters_when_creating_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $prefix = 'https://example.com/';

        $imageUrl = $prefix
            .str_repeat(
                'a',
                256 - strlen($prefix)
            );

        $this->assertSame(
            256,
            strlen($imageUrl)
        );

        $response = $this->postJson(
            route('api.v1.books.store'),
            [
                'user_id' => $user->id,
                'title' => 'API画像URL超過テスト',
                'author' => 'API著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-11',
                'description' => '256文字URLテスト',
                'image_url' => $imageUrl,
                'genres' => [
                    $genre->id,
                ],
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_url',
            ]);

        $this->assertDatabaseMissing(
            'books',
            [
                'isbn' => '9781234567890',
            ]
        );
    }

    /**
     * 書籍更新時の画像URLは255文字まで許可する
     */
    public function test_image_url_can_be_255_characters_when_updating_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $prefix = 'https://example.com/';

        $imageUrl = $prefix
            .str_repeat(
                'a',
                255 - strlen($prefix)
            );

        $this->assertSame(
            255,
            strlen($imageUrl)
        );

        $response = $this->putJson(
            route('api.v1.books.update', $book),
            [
                'user_id' => $user->id,
                'title' => 'API更新境界値テスト',
                'author' => '更新著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-12',
                'description' => '255文字URL更新テスト',
                'image_url' => $imageUrl,
                'genres' => [
                    $genre->id,
                ],
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
                'image_url' => $imageUrl,
            ]
        );
    }

    /**
     * 書籍更新時の画像URLは256文字以上の場合更新できない
     */
    public function test_image_url_cannot_exceed_255_characters_when_updating_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
            'image_url' => 'https://example.com/original.jpg',
        ]);

        $prefix = 'https://example.com/';

        $imageUrl = $prefix
            .str_repeat(
                'a',
                256 - strlen($prefix)
            );

        $this->assertSame(
            256,
            strlen($imageUrl)
        );

        $response = $this->putJson(
            route('api.v1.books.update', $book),
            [
                'user_id' => $user->id,
                'title' => 'API更新超過テスト',
                'author' => '更新著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-12',
                'description' => '256文字URL更新テスト',
                'image_url' => $imageUrl,
                'genres' => [
                    $genre->id,
                ],
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_url',
            ]);

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
                'image_url' => 'https://example.com/original.jpg',
            ]
        );
    }

    /**
     * 書籍登録時に同じジャンルIDを複数指定しても
     * distinctバリデーションエラーにならない
     */
    public function test_duplicate_genre_ids_are_not_rejected_when_creating_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->postJson(
            route('api.v1.books.store'),
            [
                'user_id' => $user->id,
                'title' => 'APIジャンル重複テスト',
                'author' => 'API著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-11',
                'description' => 'ジャンル重複テスト',
                'image_url' => null,
                'genres' => [
                    $genre->id,
                    $genre->id,
                ],
            ]
        );

        $response->assertCreated();

        $book = Book::where(
            'isbn',
            '9781234567890'
        )->firstOrFail();

        $this->assertDatabaseHas(
            'book_genre',
            [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]
        );

        $this->assertSame(
            1,
            $book->genres()->count()
        );
    }

    /**
     * 書籍更新時に同じジャンルIDを複数指定しても
     * distinctバリデーションエラーにならない
     */
    public function test_duplicate_genre_ids_are_not_rejected_when_updating_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $response = $this->putJson(
            route('api.v1.books.update', $book),
            [
                'user_id' => $user->id,
                'title' => 'APIジャンル重複更新テスト',
                'author' => 'API著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-12',
                'description' => 'ジャンル重複更新テスト',
                'image_url' => null,
                'genres' => [
                    $genre->id,
                    $genre->id,
                ],
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas(
            'book_genre',
            [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]
        );

        $book->refresh();

        $this->assertSame(
            1,
            $book->genres()->count()
        );
    }
}
