<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_genre', function (Blueprint $table) {
            // 対象書籍
            $table->foreignId('book_id')
                ->constrained('books')
                ->cascadeOnDelete();

            // 対象ジャンル
            $table->foreignId('genre_id')
                ->constrained('genres')
                ->restrictOnDelete();

            // 同じ書籍×ジャンルの重複登録を防止
            $table->primary([
                'book_id',
                'genre_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_genre');
    }
};
