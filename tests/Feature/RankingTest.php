<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはランキング画面を表示できる
     */
    public function test_guest_can_view_ranking_page(): void
    {
        $response = $this->get(
            route('ranking.index')
        );

        $response->assertOk();

        $response->assertViewIs(
            'ranking.index'
        );

        $response->assertViewHas(
            'rankedBooks'
        );
    }

    /**
     * ログインユーザーもランキング画面を表示できる
     */
    public function test_authenticated_user_can_view_ranking_page(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('ranking.index'))
            ->assertOk();
    }

    /**
     * 平均評価が高い順に表示される
     */
    public function test_books_are_ranked_by_average_rating_descending(): void
    {
        $bookFive = Book::factory()->create([
            'title' => '平均5点の書籍',
        ]);

        $bookFour = Book::factory()->create([
            'title' => '平均4点の書籍',
        ]);

        $bookThree = Book::factory()->create([
            'title' => '平均3点の書籍',
        ]);

        Review::factory()->create([
            'book_id' => $bookFive->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $bookFour->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $bookThree->id,
            'rating' => 3,
        ]);

        $response = $this->get(
            route('ranking.index')
        );

        $response->assertOk();

        $response->assertSeeInOrder([
            '平均5点の書籍',
            '平均4点の書籍',
            '平均3点の書籍',
        ]);
    }

    /**
     * 複数レビューの平均評価でランキングされる
     */
    public function test_ranking_uses_average_of_reviews(): void
    {
        $highBook = Book::factory()->create([
            'title' => '平均4.5点',
        ]);

        $lowBook = Book::factory()->create([
            'title' => '平均4点',
        ]);

        Review::factory()->create([
            'book_id' => $highBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $highBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $lowBook->id,
            'rating' => 4,
        ]);

        $response = $this->get(
            route('ranking.index')
        );

        $response->assertOk();

        $response->assertSeeInOrder([
            '平均4.5点',
            '平均4点',
        ]);
    }

    /**
     * レビューがない書籍はランキングに表示されない
     */
    public function test_book_without_reviews_is_not_displayed(): void
    {
        $reviewedBook = Book::factory()->create([
            'title' => 'レビューあり書籍',
        ]);

        Book::factory()->create([
            'title' => 'レビューなし書籍',
        ]);

        Review::factory()->create([
            'book_id' => $reviewedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get(
            route('ranking.index')
        );

        $response->assertOk();

        $response->assertSee(
            'レビューあり書籍'
        );

        $response->assertDontSee(
            'レビューなし書籍'
        );
    }

    /**
     * ランキングには最大10冊まで表示される
     */
    public function test_ranking_contains_at_most_ten_books(): void
    {
        $books = Book::factory()
            ->count(11)
            ->create();

        foreach ($books as $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this->get(
            route('ranking.index')
        );

        $response->assertOk();

        $response->assertViewHas(
            'rankedBooks',
            function ($rankedBooks) {
                return $rankedBooks->count() === 10;
            }
        );
    }

    /**
     * 平均評価とレビュー件数が取得される
     */
    public function test_average_rating_and_review_count_are_loaded(): void
    {
        $book = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        $response = $this->get(
            route('ranking.index')
        );

        $response->assertOk();

        $response->assertViewHas(
            'rankedBooks',
            function ($rankedBooks) use ($book) {
                $rankedBook = $rankedBooks->firstWhere(
                    'id',
                    $book->id
                );

                return $rankedBook !== null
                    && (float) $rankedBook->reviews_avg_rating === 4.5
                    && (int) $rankedBook->reviews_count === 2;
            }
        );
    }

    /**
     * レビューが1件もない場合でも画面を表示できる
     */
    public function test_empty_ranking_can_be_displayed(): void
    {
        Book::factory()->create([
            'title' => 'レビューなし書籍',
        ]);

        $response = $this->get(
            route('ranking.index')
        );

        $response->assertOk();

        $response->assertSee(
            'まだレビューが投稿された書籍がありません。'
        );

        $response->assertViewHas(
            'rankedBooks',
            function ($rankedBooks) {
                return $rankedBooks->isEmpty();
            }
        );
    }
}
