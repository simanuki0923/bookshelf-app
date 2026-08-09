<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは書籍登録画面へアクセスできない
     */
    public function test_guest_cannot_view_book_create_page(): void
    {
        $this->get(
            route('books.create')
        )->assertRedirect('/login');
    }

    /**
     * ログインユーザーは登録画面を表示できる
     */
    public function test_authenticated_user_can_view_book_create_page(): void
    {
        $user = User::factory()->create();

        Genre::factory()
            ->count(3)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.create'));

        $response->assertOk();

        $response->assertViewIs(
            'books.create'
        );

        $response->assertViewHas(
            'genres'
        );
    }

    /**
     * 登録画面にジャンルを表示できる
     */
    public function test_genres_are_displayed_on_create_page(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '技術書',
        ]);

        $this->actingAs($user)
            ->get(route('books.create'))
            ->assertOk()
            ->assertSee('技術書');
    }

    /**
     * ログインユーザーは書籍を登録できる
     */
    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();

        $genres = Genre::factory()
            ->count(2)
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'description' => 'Laravelの学習書籍です。',
                'image_url' => 'https://example.com/book.jpg',
                'genres' => $genres
                    ->pluck('id')
                    ->all(),
            ]);

        $book = Book::where(
            'isbn',
            '9781234567890'
        )->firstOrFail();

        $response->assertRedirect(
            route('books.show', $book)
        );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
        ]);
    }

    /**
     * 選択したジャンルを登録できる
     */
    public function test_selected_genres_are_attached_to_book(): void
    {
        $user = User::factory()->create();

        $genres = Genre::factory()
            ->count(2)
            ->create();

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'genres' => $genres
                    ->pluck('id')
                    ->all(),
            ]);

        $book = Book::where(
            'isbn',
            '9781234567890'
        )->firstOrFail();

        foreach ($genres as $genre) {
            $this->assertDatabaseHas(
                'book_genre',
                [
                    'book_id' => $book->id,
                    'genre_id' => $genre->id,
                ]
            );
        }
    }

    /**
     * 必須項目がない場合は登録できない
     */
    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('books.store'), [])
            ->assertSessionHasErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);

        $this->assertDatabaseCount(
            'books',
            0
        );
    }

    /**
     * ISBNは13桁でなければ登録できない
     */
    public function test_isbn_must_be_13_digits(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '12345',
                'published_date' => '2026-08-01',
                'genres' => [$genre->id],
            ])
            ->assertSessionHasErrors('isbn');
    }

    /**
     * ISBNは重複登録できない
     */
    public function test_duplicate_isbn_cannot_be_registered(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '9781234567890',
        ]);

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => '重複書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'genres' => [$genre->id],
            ])
            ->assertSessionHasErrors('isbn');
    }

    /**
     * ジャンルは1つ以上必要
     */
    public function test_at_least_one_genre_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'genres' => [],
            ])
            ->assertSessionHasErrors('genres');
    }

    /**
     * 存在しないジャンルは指定できない
     */
    public function test_nonexistent_genre_cannot_be_selected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'genres' => [999999],
            ])
            ->assertSessionHasErrors(
                'genres.0'
            );
    }

    /**
     * 不正な画像URLは登録できない
     */
    public function test_image_url_must_be_valid_url(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-01',
                'image_url' => 'invalid-url',
                'genres' => [$genre->id],
            ])
            ->assertSessionHasErrors(
                'image_url'
            );
    }

    /**
     * ゲストは書籍を登録できない
     */
    public function test_guest_cannot_create_book(): void
    {
        $genre = Genre::factory()->create();

        $this->post(route('books.store'), [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ])->assertRedirect('/login');

        $this->assertDatabaseMissing(
            'books',
            [
                'isbn' => '9781234567890',
            ]
        );
    }
}
