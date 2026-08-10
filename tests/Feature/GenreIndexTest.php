<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはジャンル一覧を表示できない
     */
    public function test_guest_cannot_view_genre_index(): void
    {
        $this->get(
            route('genres.index')
        )->assertRedirect('/login');
    }

    /**
     * ログインユーザーはジャンル一覧を表示できる
     */
    public function test_authenticated_user_can_view_genre_index(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();

        $response->assertViewIs(
            'genres.index'
        );

        $response->assertViewHas(
            'genres'
        );
    }

    /**
     * ジャンル名を表示できる
     */
    public function test_genres_are_displayed(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '技術書',
        ]);

        Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();

        $response->assertSee('技術書');
        $response->assertSee('小説');
    }

    /**
     * 各ジャンルの書籍件数を取得できる
     */
    public function test_book_count_is_loaded_for_each_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $books = Book::factory()
            ->count(3)
            ->create();

        $genre->books()->attach(
            $books->pluck('id')->all()
        );

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();

        $response->assertViewHas(
            'genres',
            function ($genres) use ($genre) {
                $viewGenre = $genres->firstWhere(
                    'id',
                    $genre->id
                );

                return $viewGenre !== null
                    && $viewGenre->books_count === 3;
            }
        );
    }

    /**
     * 書籍がないジャンルも0件として取得できる
     */
    public function test_genre_without_books_has_zero_book_count(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '書籍なしジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();

        $response->assertViewHas(
            'genres',
            function ($genres) use ($genre) {
                $viewGenre = $genres->firstWhere(
                    'id',
                    $genre->id
                );

                return $viewGenre !== null
                    && $viewGenre->books_count === 0;
            }
        );
    }

    /**
     * ジャンル一覧を名前順で取得する
     */
    public function test_genres_are_ordered_by_name(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '技術書',
        ]);

        Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();

        $response->assertViewHas(
            'genres',
            function ($genres) {
                return $genres->pluck('name')->values()->all()
                    === ['ビジネス', '技術書'];
            }
        );
    }

    /**
     * ジャンルが0件でも一覧画面を表示できる
     */
    public function test_empty_genre_index_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();

        $response->assertViewHas(
            'genres',
            fn ($genres) => $genres->isEmpty()
        );
    }
}
