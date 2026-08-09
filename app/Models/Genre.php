<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可する項目
     */
    protected $fillable = [
        'name',
    ];

    /**
     * ジャンルに属する書籍
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(
            Book::class,
            'book_genre'
        );
    }
}
