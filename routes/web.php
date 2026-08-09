<?php

use App\Http\Controllers\BookController;
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
    | 今後実装する書籍機能
    |--------------------------------------------------------------------------
    */

    Route::get('/books/{book}/edit', function () {
        abort(501, '書籍編集機能は未実装です。');
    })->name('books.edit');

    Route::delete('/books/{book}', function () {
        abort(501, '書籍削除機能は未実装です。');
    })->name('books.destroy');

    /*
    |--------------------------------------------------------------------------
    | 今後実装するお気に入り機能
    |--------------------------------------------------------------------------
    */

    Route::get('/favorites', function () {
        abort(501, 'お気に入り一覧機能は未実装です。');
    })->name('favorites.index');

    Route::post('/books/{book}/favorites', function () {
        abort(501, 'お気に入り機能は未実装です。');
    })->name('favorites.toggle');

    /*
    |--------------------------------------------------------------------------
    | 今後実装するレビュー機能
    |--------------------------------------------------------------------------
    */

    Route::post('/books/{book}/reviews', function () {
        abort(501, 'レビュー投稿機能は未実装です。');
    })->name('reviews.store');

    Route::get('/reviews/{review}/edit', function () {
        abort(501, 'レビュー編集機能は未実装です。');
    })->name('reviews.edit');

    Route::delete('/reviews/{review}', function () {
        abort(501, 'レビュー削除機能は未実装です。');
    })->name('reviews.destroy');

    Route::post('/reviews/{review}/like', function () {
        abort(501, 'レビューいいね機能は未実装です。');
    })->name('reviews.like');

    /*
    |--------------------------------------------------------------------------
    | 今後実装するジャンル機能
    |--------------------------------------------------------------------------
    */

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
