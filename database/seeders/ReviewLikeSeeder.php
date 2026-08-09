<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = Review::orderBy('id')->get()->values();

        foreach ($reviews as $index => $review) {
            // 0～3件のいいねを順番に設定
            $likeCount = $index % 4;

            if ($likeCount === 0) {
                continue;
            }

            // レビュー投稿者本人を除外
            $userIds = User::where(
                'id',
                '!=',
                $review->user_id
            )
                ->orderBy('id')
                ->limit($likeCount)
                ->pluck('id')
                ->all();

            $review->likedByUsers()
                ->syncWithoutDetaching($userIds);
        }
    }
}
