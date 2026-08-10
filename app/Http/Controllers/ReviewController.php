<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    /**
     * レビュー投稿
     */
    public function store(
        StoreReviewRequest $request,
        Book $book
    ): RedirectResponse {
        $validated = $request->validated();

        // ログインユーザーのレビューとして登録
        $request->user()
            ->reviews()
            ->create([
                'book_id' => $book->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);

        return redirect()
            ->route('books.show', $book)
            ->with(
                'success',
                'レビューを投稿しました。'
            );
    }
}
