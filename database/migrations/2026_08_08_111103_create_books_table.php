<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            // 書籍ID
            $table->id();

            // 書籍を登録したユーザー
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 書籍基本情報
            $table->string('title');
            $table->string('author');

            // ISBN-13（重複不可）
            $table->char('isbn', 13)->unique();

            // 出版日
            $table->date('published_date');

            // 任意項目
            $table->text('description')->nullable();
            $table->string('image_url', 2048)->nullable();

            // 作成日時・更新日時
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
