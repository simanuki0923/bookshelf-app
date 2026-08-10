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
        // 投稿者本人だけ編集可能
        $this->authorize('update', $review);

        // 編集画面で書籍情報を使用
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
        // 投稿者本人だけ更新可能
        $this->authorize('update', $review);

        // 更新後の遷移先として書籍を保持
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
}
