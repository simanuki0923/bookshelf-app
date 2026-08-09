<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可する項目
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'rating',
        'comment',
    ];

    /**
     * 型変換
     */
    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * レビュー投稿者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * レビュー対象書籍
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * レビューにいいねしたユーザー
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'review_likes'
        );
    }
}
