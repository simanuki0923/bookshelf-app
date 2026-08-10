<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
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

    // いいね登録・解除
    Route::post(
        '/reviews/{review}/like',
        [ReviewLikeController::class, 'toggle']
    )->name('reviews.like');

    /*
    |--------------------------------------------------------------------------
    | お気に入り
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/favorites',
        [FavoriteController::class, 'index']
    )->name('favorites.index');

    Route::post(
        '/books/{book}/favorites',
        [FavoriteController::class, 'toggle']
    )->name('favorites.toggle');

    /*
|--------------------------------------------------------------------------
| ジャンル
|--------------------------------------------------------------------------
*/

    // ジャンル一覧：今回実装
    Route::get(
        '/genres',
        [GenreController::class, 'index']
    )->name('genres.index');

    // ジャンル登録：後で実装
    Route::get('/genres/create', function () {
        abort(501, 'ジャンル登録機能は未実装です。');
    })->name('genres.create');

    // ジャンル編集：後で実装
    Route::get('/genres/{genre}/edit', function () {
        abort(501, 'ジャンル編集機能は未実装です。');
    })->name('genres.edit');

    // ジャンル削除：後で実装
    Route::delete('/genres/{genre}', function () {
        abort(501, 'ジャンル削除機能は未実装です。');
    })->name('genres.destroy');

    // ジャンル詳細：このfeature/genres-readで次に実装
    Route::get('/genres/{genre}', function () {
        abort(501, 'ジャンル詳細機能は未実装です。');
    })->name('genres.show');

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
