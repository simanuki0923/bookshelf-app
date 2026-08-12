<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListBooksRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    /**
     * 書籍一覧
     */
    public function index(
        ListBooksRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();

        $keyword = $validated['keyword'] ?? null;
        $genreId = $validated['genre_id'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(
                $keyword,
                function ($query, $keyword) {
                    $query->where(
                        function ($query) use ($keyword) {
                            $query
                                ->where(
                                    'title',
                                    'like',
                                    "%{$keyword}%"
                                )
                                ->orWhere(
                                    'author',
                                    'like',
                                    "%{$keyword}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $genreId,
                function ($query, $genreId) {
                    $query->whereHas(
                        'genres',
                        function ($query) use ($genreId) {
                            $query->where(
                                'genres.id',
                                $genreId
                            );
                        }
                    );
                }
            )
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return BookResource::collection(
            $books
        );
    }

    /**
     * 書籍詳細
     */
    public function show(Book $book): BookResource
    {
        $book
            ->load([
                'genres',
                'reviews.user',
            ])
            ->loadAvg(
                'reviews',
                'rating'
            )
            ->loadCount(
                'reviews'
            );

        return new BookResource($book);
    }

    /**
     * 書籍登録
     */
    public function store(
        StoreBookRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $genreIds = $validated['genres'];

        unset($validated['genres']);

        $book = DB::transaction(
            function () use (
                $validated,
                $genreIds
            ) {
                $book = Book::create(
                    $validated
                );

                $book
                    ->genres()
                    ->sync($genreIds);

                return $book;
            }
        );

        $book
            ->load('genres')
            ->loadAvg(
                'reviews',
                'rating'
            )
            ->loadCount(
                'reviews'
            );

        return (new BookResource($book))
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }

    /**
     * 書籍更新
     */
    public function update(
        UpdateBookRequest $request,
        Book $book
    ): BookResource {
        $validated = $request->validated();

        $genreIds = $validated['genres'];

        unset($validated['genres']);

        DB::transaction(
            function () use (
                $book,
                $validated,
                $genreIds
            ) {
                $book->update(
                    $validated
                );

                $book
                    ->genres()
                    ->sync($genreIds);
            }
        );

        $book
            ->load('genres')
            ->loadAvg(
                'reviews',
                'rating'
            )
            ->loadCount(
                'reviews'
            );

        return new BookResource($book);
    }

    /**
     * 書籍削除
     */
    public function destroy(
        Book $book
    ): Response {
        $book->delete();

        return response()->noContent();
    }
}
