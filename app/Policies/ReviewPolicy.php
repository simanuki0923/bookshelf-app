<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * レビューを編集できるのは投稿者本人のみ
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
