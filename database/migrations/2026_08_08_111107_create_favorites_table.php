<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            // お気に入り登録したユーザー
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // お気に入り対象書籍
            $table->foreignId('book_id')
                ->constrained('books')
                ->cascadeOnDelete();

            // 同じ書籍への重複お気に入りを防止
            $table->primary([
                'user_id',
                'book_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
