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
                'ジャンルを登録しました。'
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
                    '書籍に紐付いているジャンルは削除できません。'
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
}
