<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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

    /**
     * レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view(
            'reviews.edit',
            compact('review')
        );
    }

    /**
     * レビュー更新
     */
    public function update(
        UpdateReviewRequest $request,
        Review $review
    ): RedirectResponse {
        $this->authorize('update', $review);

        $book = $review->book;

        $review->update(
            $request->validated()
        );

        return redirect()
            ->route('books.show', $book)
            ->with(
                'success',
                'レビューを更新しました。'
            );
    }

    /**
     * レビュー削除
     */
    public function destroy(Review $review): RedirectResponse
    {
        // 投稿者本人か確認
        $this->authorize('delete', $review);

        // 削除後の遷移先を先に保持
        $book = $review->book;

        // レビュー削除
        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with(
                'success',
                'レビューを削除しました。'
            );
    }
}
