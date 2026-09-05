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
            ])
            ->assertJsonMissingPath(
                'data.0.user_id'
            )
            ->assertJsonMissingPath(
                'data.0.created_at'
            )
            ->assertJsonMissingPath(
                'data.0.updated_at'
            );
    }

    /**
     * タイトルの部分一致で書籍を検索できる
     */
    public function test_books_can_be_searched_by_title_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '鈴木一郎',
        ]);

        $response = $this->getJson(
            route('api.v1.books.index', [
                'keyword' => 'Laravel',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.title',
                'Laravel入門'
            );
    }

    /**
     * 著者名の部分一致で書籍を検索できる
     */
    public function test_books_can_be_searched_by_author_keyword(): void
    {
        Book::factory()->create([
            'title' => '書籍A',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => '書籍B',
            'author' => '鈴木一郎',
        ]);

        $response = $this->getJson(
            route('api.v1.books.index', [
                'keyword' => '山田',
            ])
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.author',
                '山田太郎'
            );
    }

    /**
     * ジャンルIDで書籍を絞り込める
     */
    public function test_books_can_be_filtered_by_genre_id(): void
    {
        $targetGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();

        $targetBook = Book::factory()->create([
            'title' => '対象書籍',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '対象外書籍',
        ]);

        $targetBook->genres()->attach(
            $targetGenre->id
        );

        $otherBook->genres()->attach(
            $otherGenre->id
        );

        $response = $this->getJson(
            route('api.v1.books.index', [
                'genre_id' => $targetGenre->id,
            ])
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $targetBook->id
            );
    }

    /**
     * キーワードとジャンルIDを組み合わせて絞り込める
     */
    public function test_keyword_and_genre_id_can_be_combined(): void
    {
        $targetGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();

        $targetBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $sameKeywordBook = Book::factory()->create([
            'title' => 'Laravel実践',
            'author' => '鈴木一郎',
        ]);

        $targetBook->genres()->attach(
            $targetGenre->id
        );

        $sameKeywordBook->genres()->attach(
            $otherGenre->id
        );

        $response = $this->getJson(
            route('api.v1.books.index', [
                'keyword' => 'Laravel',
                'genre_id' => $targetGenre->id,
            ])
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $targetBook->id
            );
    }

    /**
     * 1ページあたりの取得件数を指定できる
     */
    public function test_per_page_can_be_specified(): void
    {
        Book::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(
            route('api.v1.books.index', [
                'per_page' => 2,
            ])
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'meta.per_page',
                2
            );
    }

    /**
     * per_page未指定時は20件取得する
     */
    public function test_default_per_page_is_twenty(): void
    {
        Book::factory()
            ->count(21)
            ->create();

        $response = $this->getJson(
            route('api.v1.books.index')
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                20,
                'data'
            )
            ->assertJsonPath(
                'meta.per_page',
                20
            );
    }

    /**
     * ページ番号を指定できる
     */
    public function test_page_can_be_specified(): void
    {
        Book::factory()
            ->count(3)
            ->create();

        $response = $this->getJson(
            route('api.v1.books.index', [
                'per_page' => 2,
                'page' => 2,
            ])
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.current_page',
                2
            )
            ->assertJsonCount(
                1,
                'data'
            );
    }

    /**
     * 存在しないジャンルIDは指定できない
     */
    public function test_nonexistent_genre_id_returns_validation_error(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'genre_id' => 999999,
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id',
            ]);
    }

    /**
     * pageは1以上でなければならない
     */
    public function test_page_must_be_at_least_one(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'page' => 0,
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'page',
            ]);
    }

    /**
     * per_pageは1以上でなければならず、
     * バリデーションエラーはLaravel標準JSON形式で返す
     */
    public function test_per_page_must_be_at_least_one(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'per_page' => 0,
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'per_page',
                ],
            ])
            ->assertJsonValidationErrors([
                'per_page',
            ]);
    }

    /**
     * per_pageは100以下でなければならない
     */
    public function test_per_page_cannot_exceed_one_hundred(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'per_page' => 101,
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page',
            ]);
    }

    /**
     * keywordは文字列でなければならない
     */
    public function test_keyword_must_be_string(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'keyword' => [
                    'Laravel',
                ],
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'keyword',
            ]);
    }

    /**
     * keywordは255文字以下でなければならない
     */
    public function test_keyword_cannot_exceed_255_characters(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'keyword' => str_repeat(
                    'a',
                    256
                ),
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'keyword',
            ]);
    }

    /**
     * genre_idは整数でなければならない
     */
    public function test_genre_id_must_be_integer(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'genre_id' => 'invalid',
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id',
            ]);
    }

    /**
     * pageは整数でなければならない
     */
    public function test_page_must_be_integer(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'page' => 'invalid',
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'page',
            ]);
    }

    /**
     * per_pageは整数でなければならない
     */
    public function test_per_page_must_be_integer(): void
    {
        $response = $this->getJson(
            route('api.v1.books.index', [
                'per_page' => 'invalid',
            ])
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page',
            ]);
    }

    /**
     * 書籍一覧にジャンル・平均評価・レビュー件数を含める
     */
    public function test_book_index_contains_genres_average_rating_and_review_count(): void
    {
        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create();

        $book->genres()->attach(
            $genre->id
        );

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 2,
        ]);

        $response = $this->getJson(
            route('api.v1.books.index')
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $book->id
            )
            ->assertJsonPath(
                'data.0.genres.0.name',
                '技術書'
            )
            ->assertJsonPath(
                'data.0.average_rating',
                3
            )
            ->assertJsonPath(
                'data.0.review_count',
                2
            )
            ->assertJsonMissingPath(
                'data.0.reviews_count'
            );
    }

    /**
     * 平均評価は小数第1位までで返す
     */
    public function test_average_rating_is_rounded_to_one_decimal_place(): void
    {
        $book = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.average_rating',
                4.3
            );
    }

    /**
     * レビューが存在しない場合の平均評価はnullで返す
     */
    public function test_average_rating_is_null_when_book_has_no_reviews(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.average_rating',
                null
            );
    }

    /**
     * 書籍詳細にレビュー件数をreview_countで含める
     */
    public function test_book_detail_contains_review_count(): void
    {
        $book = Book::factory()->create();

        Review::factory()
            ->count(2)
            ->create([
                'book_id' => $book->id,
            ]);

        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.review_count',
                2
            )
            ->assertJsonMissingPath(
                'data.reviews_count'
            );
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

        $response
            ->assertCreated()
            ->assertJsonMissingPath(
                'data.user_id'
            )
            ->assertJsonMissingPath(
                'data.created_at'
            )
            ->assertJsonMissingPath(
                'data.updated_at'
            );

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
            )
            ->assertJsonMissingPath(
                'data.user_id'
            )
            ->assertJsonMissingPath(
                'data.created_at'
            )
            ->assertJsonMissingPath(
                'data.updated_at'
            );
    }

    /**
     * 存在しない書籍詳細は404とJSONエラーを返す
     */
    public function test_nonexistent_book_returns_404_with_json_error(): void
    {
        $response = $this->getJson(
            '/api/v1/books/999999'
        );

        $response
            ->assertNotFound()
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
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
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
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
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
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

        $response
            ->assertOk()
            ->assertJsonMissingPath(
                'data.user_id'
            )
            ->assertJsonMissingPath(
                'data.created_at'
            )
            ->assertJsonMissingPath(
                'data.updated_at'
            );

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

        $review = Review::factory()->create([
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
                'data.reviews.0.id',
                $review->id
            )
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
            )
            ->assertJsonPath(
                'data.reviews.0.created_at',
                $review->created_at->toISOString()
            );
    }

    /**
     * 書籍詳細に複数のレビューを含める
     */
    public function test_book_detail_contains_multiple_reviews(): void
    {
        $book = Book::factory()->create();

        Review::factory()
            ->count(2)
            ->create([
                'book_id' => $book->id,
            ]);

        $response = $this->getJson(
            route('api.v1.books.show', $book)
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.reviews'
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

        $originalImageUrl = 'https://example.com/original.jpg';

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '9781234567890',
            'image_url' => $originalImageUrl,
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
                'image_url' => $originalImageUrl,
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
