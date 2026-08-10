<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// 書籍一覧
Route::get(
    '/',
    [BookController::class, 'index']
)->name('books.index');

// 認証必須
Route::middleware('auth')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | 書籍登録
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/books/create',
        [BookController::class, 'create']
    )->name('books.create');

    Route::post(
        '/books',
        [BookController::class, 'store']
    )->name('books.store');

    /*
    |--------------------------------------------------------------------------
    | 書籍編集
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/books/{book}/edit',
        [BookController::class, 'edit']
    )->name('books.edit');

    Route::put(
        '/books/{book}',
        [BookController::class, 'update']
    )->name('books.update');

    /*
    |--------------------------------------------------------------------------
    | 書籍削除
    |--------------------------------------------------------------------------
    */

    Route::delete(
        '/books/{book}',
        [BookController::class, 'destroy']
    )->name('books.destroy');

    /* /*
    |--------------------------------------------------------------------------
    | レビュー投稿
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/books/{book}/reviews',
        [ReviewController::class, 'store']
    )->name('reviews.store');

    // レビュー編集
    Route::get(
        '/reviews/{review}/edit',
        [ReviewController::class, 'edit']
    )->name('reviews.edit');

    // レビュー更新
    Route::put(
        '/reviews/{review}',
        [ReviewController::class, 'update']
    )->name('reviews.update');

    // 削除
    Route::delete(
        '/reviews/{review}',
        [ReviewController::class, 'destroy']
    )->name('reviews.destroy');

    Route::get('/favorites', function () {
        abort(501, 'お気に入り一覧機能は未実装です。');
    })->name('favorites.index');

    Route::post('/books/{book}/favorites', function () {
        abort(501, 'お気に入り機能は未実装です。');
    })->name('favorites.toggle');

    Route::post('/reviews/{review}/like', function () {
        abort(501, 'レビューいいね機能は未実装です。');
    })->name('reviews.like');

    Route::get('/genres', function () {
        abort(501, 'ジャンル管理機能は未実装です。');
    })->name('genres.index');
});

// ランキング
Route::get('/ranking', function () {
    abort(501, 'ランキング機能は未実装です。');
})->name('ranking.index');

// 書籍詳細
Route::get(
    '/books/{book}',
    [BookController::class, 'show']
)->name('books.show');
