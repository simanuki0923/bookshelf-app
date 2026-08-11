<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはジャンル編集画面へアクセスできない
     */
    public function test_guest_cannot_view_genre_edit_page(): void
    {
        $genre = Genre::factory()->create();

        $this->get(
            route('genres.edit', $genre)
        )->assertRedirect('/login');
    }

    /**
     * ログインユーザーはジャンル編集画面を表示できる
     */
    public function test_authenticated_user_can_view_genre_edit_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.edit', $genre));

        $response->assertOk();

        $response->assertViewIs(
            'genres.edit'
        );

        $response->assertViewHas(
            'genre',
            fn ($viewGenre) => $viewGenre->id === $genre->id
        );

        $response->assertSee('技術書');
    }

    /**
     * ログインユーザーはジャンルを更新できる
     */
    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '旧ジャンル名',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '新ジャンル名',
            ]);

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'success',
            'ジャンルを更新しました。'
        );

        $this->assertDatabaseHas(
            'genres',
            [
                'id' => $genre->id,
                'name' => '新ジャンル名',
            ]
        );

        $this->assertDatabaseMissing(
            'genres',
            [
                'id' => $genre->id,
                'name' => '旧ジャンル名',
            ]
        );
    }

    /**
     * 現在のジャンル名のまま更新できる
     */
    public function test_current_genre_name_can_be_kept(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '技術書',
            ]);

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionDoesntHaveErrors(
            'name'
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
     * 他のジャンルと同じ名前には変更できない
     */
    public function test_duplicate_genre_name_cannot_be_used(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '小説',
        ]);

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '小説',
            ])
            ->assertSessionHasErrors(
                'name'
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
     * ジャンル名は必須
     */
    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '',
            ])
            ->assertSessionHasErrors(
                'name'
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
     * ジャンル名は255文字以内
     */
    public function test_name_cannot_exceed_255_characters(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => str_repeat('あ', 256),
            ])
            ->assertSessionHasErrors(
                'name'
            );
    }

    /**
     * ゲストはジャンルを直接更新できない
     */
    public function test_guest_cannot_update_genre(): void
    {
        $genre = Genre::factory()->create([
            'name' => '変更前',
        ]);

        $this->put(
            route('genres.update', $genre),
            [
                'name' => '不正変更',
            ]
        )->assertRedirect('/login');

        $this->assertDatabaseHas(
            'genres',
            [
                'id' => $genre->id,
                'name' => '変更前',
            ]
        );
    }

    /**
     * 存在しないジャンルの編集画面は404
     */
    public function test_nonexistent_genre_edit_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/genres/999999/edit')
            ->assertNotFound();
    }

    /**
     * 存在しないジャンルの更新は404
     */
    public function test_nonexistent_genre_update_returns_404(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->put('/genres/999999', [
                'name' => '存在しないジャンル',
            ])
            ->assertNotFound();
    }
}
