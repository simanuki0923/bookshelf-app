<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
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

    // 一覧
    Route::get(
        '/genres',
        [GenreController::class, 'index']
    )->name('genres.index');

    // 登録画面
    Route::get(
        '/genres/create',
        [GenreController::class, 'create']
    )->name('genres.create');

    // 登録
    Route::post(
        '/genres',
        [GenreController::class, 'store']
    )->name('genres.store');

    // 編集画面
    Route::get(
        '/genres/{genre}/edit',
        [GenreController::class, 'edit']
    )->name('genres.edit');

    // 更新
    Route::put(
        '/genres/{genre}',
        [GenreController::class, 'update']
    )->name('genres.update');

    // 詳細
    Route::get(
        '/genres/{genre}',
        [GenreController::class, 'show']
    )->name('genres.show');

    // 削除
    Route::delete(
        '/genres/{genre}',
        [GenreController::class, 'destroy']
    )->name('genres.destroy');
});

// ランキング
Route::get(
    '/ranking',
    [RankingController::class, 'index']
)->name('ranking.index');

// 書籍詳細
Route::get(
    '/books/{book}',
    [BookController::class, 'show']
)->name('books.show');
