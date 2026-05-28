<?php

namespace App\Models\Concerns;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
}
