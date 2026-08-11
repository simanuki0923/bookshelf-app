<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは公開画面を表示できる
     */
    public function test_guest_can_view_public_pages(): void
    {
        $book = Book::factory()->create([
            'title' => '公開テスト書籍',
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $this->get(
            route('books.index')
        )->assertOk();

        $this->get(
            route('books.show', $book)
        )->assertOk();

        $this->get(
            route('ranking.index')
        )->assertOk();
    }

    /**
     * ゲストは認証必須画面へアクセスできない
     */
    public function test_guest_cannot_view_authenticated_pages(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $this->get(
            route('books.create')
        )->assertRedirect('/login');

        $this->get(
            route('favorites.index')
        )->assertRedirect('/login');

        $this->get(
            route('genres.index')
        )->assertRedirect('/login');

        $this->get(
            route('genres.show', $genre)
        )->assertRedirect('/login');

        $this->get(
            route('genres.create')
        )->assertRedirect('/login');

        $this->get(
            route('genres.edit', $genre)
        )->assertRedirect('/login');

        $this->get(
            route('reviews.edit', $review)
        )->assertRedirect('/login');
    }

    /**
     * ログインユーザーは基本的な認証必須画面を表示できる
     */
    public function test_authenticated_user_can_view_basic_authenticated_pages(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this
            ->actingAs($user)
            ->get(route('books.create'))
            ->assertOk();

        $this
            ->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk();

        $this
            ->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk();

        $this
            ->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk();

        $this
            ->actingAs($user)
            ->get(route('genres.create'))
            ->assertOk();

        $this
            ->actingAs($user)
            ->get(route('genres.edit', $genre))
            ->assertOk();

        $this
            ->actingAs($user)
            ->get(route('reviews.edit', $review))
            ->assertOk();
    }

    /**
     * 基本Web機能を一連の操作として実行できる
     */
    public function test_basic_web_features_work_together(): void
    {
        $user = User::factory()->create();

        /*
        |--------------------------------------------------------------------------
        | 1. ジャンル登録
        |--------------------------------------------------------------------------
        */

        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '総合テストジャンル',
            ]);

        $response->assertRedirect(
            route('genres.index')
        );

        $genre = Genre::query()
            ->where('name', '総合テストジャンル')
            ->firstOrFail();

        $this->assertDatabaseHas(
            'genres',
            [
                'id' => $genre->id,
                'name' => '総合テストジャンル',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2. 書籍登録
        |--------------------------------------------------------------------------
        */

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => '総合テスト書籍',
                'author' => '総合テスト著者',
                'isbn' => '9781234567890',
                'published_date' => '2026-08-11',
                'description' => '基本Web機能総合確認用の書籍です。',
                'image_url' => 'https://example.com/book.jpg',
                'genres' => [
                    $genre->id,
                ],
            ]);

        $book = Book::query()
            ->where('isbn', '9781234567890')
            ->firstOrFail();

        $response->assertRedirect(
            route('books.show', $book)
        );

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
                'user_id' => $user->id,
                'title' => '総合テスト書籍',
            ]
        );

        $this->assertDatabaseHas(
            'book_genre',
            [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3. レビュー投稿
        |--------------------------------------------------------------------------
        */

        $response = $this
            ->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 4,
                    'comment' => '総合テストレビュー',
                ]
            );

        $response->assertRedirect(
            route('books.show', $book)
        );

        $review = Review::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertDatabaseHas(
            'reviews',
            [
                'id' => $review->id,
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 4,
                'comment' => '総合テストレビュー',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4. お気に入り登録
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            )
            ->assertRedirect(
                route('books.show', $book)
            );

        $this->assertDatabaseHas(
            'favorites',
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5. レビューいいね
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            )
            ->assertRedirect(
                route('books.show', $book)
            );

        $this->assertDatabaseHas(
            'review_likes',
            [
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 6. お気に入り一覧
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('総合テスト書籍');

        /*
        |--------------------------------------------------------------------------
        | 7. ジャンル詳細
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('総合テスト書籍');

        /*
        |--------------------------------------------------------------------------
        | 8. ランキング
        |--------------------------------------------------------------------------
        */

        $this
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('総合テスト書籍');

        /*
        |--------------------------------------------------------------------------
        | 9. レビュー更新
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->put(
                route('reviews.update', $review),
                [
                    'rating' => 5,
                    'comment' => '更新後レビュー',
                ]
            )
            ->assertRedirect(
                route('books.show', $book)
            );

        $this->assertDatabaseHas(
            'reviews',
            [
                'id' => $review->id,
                'rating' => 5,
                'comment' => '更新後レビュー',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 10. 書籍更新
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->put(
                route('books.update', $book),
                [
                    'title' => '総合テスト書籍（更新）',
                    'author' => '総合テスト著者',
                    'isbn' => '9781234567890',
                    'published_date' => '2026-08-11',
                    'description' => '更新後の説明です。',
                    'image_url' => 'https://example.com/book.jpg',
                    'genres' => [
                        $genre->id,
                    ],
                ]
            )
            ->assertRedirect(
                route('books.show', $book)
            );

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
                'title' => '総合テスト書籍（更新）',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 11. ジャンル更新
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->put(
                route('genres.update', $genre),
                [
                    'name' => '総合テストジャンル（更新）',
                ]
            )
            ->assertRedirect(
                route('genres.index')
            );

        $this->assertDatabaseHas(
            'genres',
            [
                'id' => $genre->id,
                'name' => '総合テストジャンル（更新）',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 12. お気に入り解除
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route('favorites.toggle', $book)
            );

        $this->assertDatabaseMissing(
            'favorites',
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 13. レビューいいね解除
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->post(
                route('reviews.like', $review)
            );

        $this->assertDatabaseMissing(
            'review_likes',
            [
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 14. レビュー削除
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->delete(
                route('reviews.destroy', $review)
            )
            ->assertRedirect(
                route('books.show', $book)
            );

        $this->assertDatabaseMissing(
            'reviews',
            [
                'id' => $review->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 15. レビュー削除後はランキング対象外
        |--------------------------------------------------------------------------
        */

        $this
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertDontSee(
                '総合テスト書籍（更新）'
            );

        /*
        |--------------------------------------------------------------------------
        | 16. 書籍削除
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->delete(
                route('books.destroy', $book)
            )
            ->assertRedirect(
                route('books.index')
            );

        $this->assertDatabaseMissing(
            'books',
            [
                'id' => $book->id,
            ]
        );

        $this->assertDatabaseMissing(
            'book_genre',
            [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 17. 未使用になったジャンルを削除
        |--------------------------------------------------------------------------
        */

        $this
            ->actingAs($user)
            ->delete(
                route('genres.destroy', $genre)
            )
            ->assertRedirect(
                route('genres.index')
            );

        $this->assertDatabaseMissing(
            'genres',
            [
                'id' => $genre->id,
            ]
        );
    }
}
