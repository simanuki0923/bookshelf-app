<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            // レビューID
            $table->id();

            // 投稿ユーザー
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // レビュー対象書籍
            $table->foreignId('book_id')
                ->constrained('books')
                ->cascadeOnDelete();

            // 評価（1～5）
            $table->unsignedTinyInteger('rating');

            // レビューコメント
            $table->text('comment')->nullable();

            // 作成日時・更新日時
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};