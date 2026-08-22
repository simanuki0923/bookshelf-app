<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_likes', function (Blueprint $table) {
            // いいねしたユーザー
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // いいね対象レビュー
            $table->foreignId('review_id')
                ->constrained('reviews')
                ->cascadeOnDelete();

            // 同じレビューへの重複いいねを防止
            $table->primary([
                'user_id',
                'review_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_likes');
    }
};
