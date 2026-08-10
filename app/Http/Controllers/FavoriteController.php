<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧
     */
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->paginate(10);

        return view(
            'favorites.index',
            compact('books')
        );
    }

    /**
     * お気に入り登録・解除
     */
    public function toggle(
        Request $request,
        Book $book
    ): RedirectResponse {
        $result = $request->user()
            ->favoriteBooks()
            ->toggle($book->id);

        $message = ! empty($result['attached'])
            ? 'お気に入りに追加しました。'
            : 'お気に入りから解除しました。';

        return redirect()
            ->route('books.show', $book)
            ->with('success', $message);
    }
}
