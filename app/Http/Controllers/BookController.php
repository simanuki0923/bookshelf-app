<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧
     */
    public function index(): View
    {
        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->latest('created_at')
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍詳細
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query
                    ->with([
                        'user',
                        'likedByUsers',
                    ])
                    ->latest();
            },
        ]);

        if (Auth::check()) {
            Auth::user()->loadMissing([
                'favoriteBooks',
                'likedReviews',
            ]);
        }

        return view('books.show', compact('book'));
    }

    /**
     * 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view(
            'books.create',
            compact('genres')
        );
    }

    /**
     * 書籍登録
     */
    public function store(
        StoreBookRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        $genreIds = $validated['genres'];

        unset($validated['genres']);

        $book = DB::transaction(function () use (
            $request,
            $validated,
            $genreIds
        ) {
            $book = $request->user()
                ->books()
                ->create($validated);

            $book->genres()->sync($genreIds);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with(
                'success',
                '書籍を登録しました。'
            );
    }

    /**
     * 書籍編集画面
     */
    public function edit(Book $book): View
    {
        // 登録者本人か確認
        $this->authorize('update', $book);

        // 現在設定されているジャンルを取得
        $book->load('genres');

        // 編集画面の選択肢
        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view(
            'books.edit',
            compact('book', 'genres')
        );
    }

    /**
     * 書籍更新
     */
    public function update(
        UpdateBookRequest $request,
        Book $book
    ): RedirectResponse {
        // 登録者本人か確認
        $this->authorize('update', $book);

        $validated = $request->validated();

        // genresはbooksテーブルへ保存しない
        $genreIds = $validated['genres'];

        unset($validated['genres']);

        // 書籍情報とジャンルをまとめて更新
        DB::transaction(function () use (
            $book,
            $validated,
            $genreIds
        ) {
            $book->update($validated);

            $book->genres()->sync($genreIds);
        });

        return redirect()
            ->route('books.show', $book)
            ->with(
                'success',
                '書籍情報を更新しました。'
            );
    }

    /**
     * 書籍削除
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with(
                'success',
                '書籍を削除しました。'
            );
    }
}
