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
                'image_url' => null,
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
            ]
        );
    }

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

    public function test_nonexistent_book_returns_404(): void
    {
        $this->getJson(
            '/api/v1/books/999999'
        )->assertNotFound();
    }

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
                'image_url' => null,
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
            ]
        );
    }

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
}
