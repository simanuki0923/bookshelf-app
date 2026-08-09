<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // 一括代入を許可する項目
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // JSON変換時に非表示にする項目
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 型変換
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ユーザーが登録した書籍
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    // ユーザーが投稿したレビュー
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ユーザーがお気に入り登録した書籍
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites');
    }

    // ユーザーがいいねしたレビュー
    public function likedReviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_likes');
    }
}
