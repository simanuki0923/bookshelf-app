<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

// 書籍一覧
Route::get('/', [BookController::class, 'index'])
    ->name('books.index');

// 認証必須の今後実装する機能
Route::middleware('auth')->group(function () {
    Route::get('/books/create', function () {
        abort(501, '書籍登録機能は未実装です。');
    })->name('books.create');

    Route::get('/favorites', function () {
        abort(501, 'お気に入り機能は未実装です。');
    })->name('favorites.index');

    Route::get('/genres', function () {
        abort(501, 'ジャンル管理機能は未実装です。');
    })->name('genres.index');
});

// ランキング
Route::get('/ranking', function () {
    abort(501, 'ランキング機能は未実装です。');
})->name('ranking.index');

// 書籍詳細
Route::get('/books/{book}', function () {
    abort(501, '書籍詳細機能は未実装です。');
})->name('books.show');
