<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧
     */
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return view(
            'genres.index',
            compact('genres')
        );
    }

    /**
     * ジャンル詳細
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with('genres')
            ->orderByDesc('books.created_at')
            ->paginate(10);

        return view(
            'genres.show',
            compact('genre', 'books')
        );
    }

    /**
     * ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンル登録
     */
    public function store(
        StoreGenreRequest $request
    ): RedirectResponse {
        Genre::create(
            $request->validated()
        );

        return redirect()
            ->route('genres.index')
            ->with(
                'success',
                'ジャンルを作成しました。'
            );
    }

    /**
     * ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        return view(
            'genres.edit',
            compact('genre')
        );
    }

    /**
     * ジャンル更新
     */
    public function update(
        UpdateGenreRequest $request,
        Genre $genre
    ): RedirectResponse {
        $genre->update(
            $request->validated()
        );

        return redirect()
            ->route('genres.index')
            ->with(
                'success',
                'ジャンルを更新しました。'
            );
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with(
                    'error',
                    'このジャンルには書籍が紐付いているため削除できません。'
                );
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with(
                'success',
                'ジャンルを削除しました。'
            );
    }

    /**
     * お気に入り書籍一覧は書籍の最新登録順で表示される
     */
    public function test_favorite_books_are_displayed_in_latest_book_order(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'title' => '古いお気に入り書籍',
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しいお気に入り書籍',
            'created_at' => now(),
        ]);

        $user->favoriteBooks()->attach([
            $oldBook->id,
            $newBook->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $response->assertViewHas(
            'books',
            function ($books) use ($newBook, $oldBook) {
                return $books
                    ->pluck('id')
                    ->values()
                    ->all() === [
                        $newBook->id,
                        $oldBook->id,
                    ];
            }
        );
    }
}
