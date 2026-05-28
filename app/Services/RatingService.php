<?php

namespace App\Services;

use App\Models\Contracts\Rateable;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Webmozart\Assert\Assert;

class RatingService
{
    /**
     * Set the given user's rating for the entity. A rating of 0 removes the rating;
     * 1-5 upserts.
     */
    public function setRating(Rateable&Model $rateable, User $user, int $rating): void
    {
        Assert::range($rating, 0, 5);

        if ($rating === 0) {
            $rateable->ratings()->where('user_id', $user->id)->delete();

            return;
        }

        Rating::query()->upsert(
            [
                [
                    'user_id' => $user->id,
                    'rateable_id' => $rateable->getKey(),
                    'rateable_type' => $rateable->getMorphClass(),
                    'rating' => $rating,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['user_id', 'rateable_id', 'rateable_type'],
            ['rating', 'updated_at'],
        );
    }
}
