<?php

namespace App\Http\Controllers;

use App\Models\Book;
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
}
