<?php

namespace App\Models\Concerns;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection<int, Rating> $ratings
 */
trait MorphsToRatings
{
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function getRatingFor(User $user): int
    {
        if ($this->relationLoaded('ratings')) {
            return (int) $this->ratings->firstWhere('user_id', $user->id)?->rating;
        }

        return (int) $this->ratings()->where('user_id', $user->id)->value('rating');
    }
    public function getStarRatingFor(User $user): int
{
    return max(0, min(5, $this->getRatingFor($user)));
}

public function hasStarRatingFor(User $user): bool
{
    return $this->getStarRatingFor($user) > 0;
}

public function isHighlyRatedBy(User $user): bool
{
    return $this->getStarRatingFor($user) >= 4;
}

public function shouldAvoidPlaybackFor(User $user): bool
{
    return $this->getStarRatingFor($user) === 1;
}
}
