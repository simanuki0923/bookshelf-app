<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーは未使用ジャンルを削除できる
     */
    public function test_authenticated_user_can_delete_unused_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '削除対象ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                route('genres.destroy', $genre)
            );

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );

        $this->assertDatabaseMissing(
            'genres',
            [
                'id' => $genre->id,
            ]
        );
    }

    /**
     * 書籍に紐付いているジャンルは削除できない
     */
    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create();

        $genre->books()
            ->attach($book->id);

        $response = $this
            ->actingAs($user)
            ->delete(
                route('genres.destroy', $genre)
            );

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'error',
            '書籍に紐付いているジャンルは削除できません。'
        );

        $this->assertDatabaseHas(
            'genres',
            [
                'id' => $genre->id,
                'name' => '技術書',
            ]
        );
    }

    /**
     * 削除拒否時も書籍との関連は残る
     */
    public function test_book_genre_relationship_remains_when_delete_is_rejected(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create();

        $genre->books()
            ->attach($book->id);

        $this
            ->actingAs($user)
            ->delete(
                route('genres.destroy', $genre)
            );

        $this->assertDatabaseHas(
            'book_genre',
            [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]
        );
    }

    /**
     * ゲストはジャンルを削除できない
     */
    public function test_guest_cannot_delete_genre(): void
    {
        $genre = Genre::factory()->create([
            'name' => '削除されないジャンル',
        ]);

        $this->delete(
            route('genres.destroy', $genre)
        )->assertRedirect('/login');

        $this->assertDatabaseHas(
            'genres',
            [
                'id' => $genre->id,
                'name' => '削除されないジャンル',
            ]
        );
    }

    /**
     * 他のジャンルには影響しない
     */
    public function test_deleting_genre_does_not_affect_other_genres(): void
    {
        $user = User::factory()->create();

        $deleteGenre = Genre::factory()->create([
            'name' => '削除するジャンル',
        ]);

        $keepGenre = Genre::factory()->create([
            'name' => '残すジャンル',
        ]);

        $this
            ->actingAs($user)
            ->delete(
                route('genres.destroy', $deleteGenre)
            );

        $this->assertDatabaseMissing(
            'genres',
            [
                'id' => $deleteGenre->id,
            ]
        );

        $this->assertDatabaseHas(
            'genres',
            [
                'id' => $keepGenre->id,
                'name' => '残すジャンル',
            ]
        );
    }

    /**
     * ジャンル一覧に削除フォームが表示される
     */
    public function test_delete_form_is_displayed_on_genre_index(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk()
            ->assertSee(
                'action="'.route('genres.destroy', $genre).'"',
                false
            );
    }

    /**
     * 存在しないジャンルの削除は404
     */
    public function test_nonexistent_genre_delete_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->delete('/genres/999999')
            ->assertNotFound();
    }
}
