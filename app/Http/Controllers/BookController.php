<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
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
        // 登録フォームに表示するジャンル
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

        // ジャンルIDはbooksテーブルへ保存しない
        $genreIds = $validated['genres'];

        unset($validated['genres']);

        // 書籍とジャンルをまとめて登録
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
}
