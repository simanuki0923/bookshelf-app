<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはジャンル登録画面を表示できない
     */
    public function test_guest_cannot_view_genre_create_page(): void
    {
        $this->get(
            route('genres.create')
        )->assertRedirect('/login');
    }

    /**
     * ログインユーザーはジャンル登録画面を表示できる
     */
    public function test_authenticated_user_can_view_genre_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('genres.create'));

        $response->assertOk();

        $response->assertViewIs(
            'genres.create'
        );
    }

    /**
     * ログインユーザーはジャンルを登録できる
     */
    public function test_authenticated_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '技術書',
            ]);

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'success',
            'ジャンルを登録しました。'
        );

        $this->assertDatabaseHas(
            'genres',
            [
                'name' => '技術書',
            ]
        );
    }

    /**
     * ゲストはジャンルを登録できない
     */
    public function test_guest_cannot_create_genre(): void
    {
        $this->post(
            route('genres.store'),
            [
                'name' => '技術書',
            ]
        )->assertRedirect('/login');

        $this->assertDatabaseMissing(
            'genres',
            [
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

        $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors(
                'name'
            );

        $this->assertDatabaseCount(
            'genres',
            0
        );
    }

    /**
     * 同じジャンル名は重複登録できない
     */
    public function test_duplicate_genre_name_cannot_be_created(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '技術書',
        ]);

        $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '技術書',
            ])
            ->assertSessionHasErrors(
                'name'
            );

        $this->assertDatabaseCount(
            'genres',
            1
        );
    }

    /**
     * ジャンル名は255文字を超えて登録できない
     */
    public function test_name_cannot_exceed_255_characters(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => str_repeat('あ', 256),
            ])
            ->assertSessionHasErrors(
                'name'
            );

        $this->assertDatabaseCount(
            'genres',
            0
        );
    }
}
