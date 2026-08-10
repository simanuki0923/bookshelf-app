<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧
     */
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return view(
            'genres.index',
            compact('genres')
        );
    }
}
