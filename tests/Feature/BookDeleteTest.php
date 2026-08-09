<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 登録者本人は書籍を削除できる
     */
    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->delete(
                route('books.destroy', $book)
            );

        $response->assertRedirect(
            route('books.index')
        );

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );

        $this->assertDatabaseMissing(
            'books',
            [
                'id' => $book->id,
            ]
        );
    }

    /**
     * ゲストは書籍を削除できない
     */
    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $this->delete(
            route('books.destroy', $book)
        )->assertRedirect('/login');

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
            ]
        );
    }

    /**
     * 他ユーザーは書籍を削除できない
     */
    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->delete(
                route('books.destroy', $book)
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'books',
            [
                'id' => $book->id,
            ]
        );
    }

    /**
     * 登録者本人には削除ボタンを表示する
     */
    public function test_delete_button_is_visible_to_owner(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($owner)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('削除');
    }

    /**
     * 他ユーザーには書籍削除フォームを表示しない
     */
    public function test_delete_button_is_not_visible_to_other_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this
            ->actingAs($otherUser)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee(
                'action="'.route('books.destroy', $book).'"',
                false
            );
    }
}
