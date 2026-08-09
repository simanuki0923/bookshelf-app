<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示
     */
    public function index(): View
    {
        $books = Book::query()
            // ジャンルをまとめて取得
            ->with('genres')

            // レビューの平均評価を取得
            ->withAvg('reviews', 'rating')

            // 新しく登録された書籍から表示
            ->latest('created_at')

            // 1ページ10件
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍詳細を表示
     */
    public function show(Book $book): View
    {
        // 詳細画面で使用する関連データをまとめて取得
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

        // ログイン中はお気に入り・いいね済み判定用データを取得
        if (Auth::check()) {
            Auth::user()->loadMissing([
                'favoriteBooks',
                'likedReviews',
            ]);
        }

        return view('books.show', compact('book'));
    }
}
