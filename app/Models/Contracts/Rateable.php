<?php

namespace App\Models\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 */
interface Rateable
{
    public function ratings(): MorphMany;

    public function getRatingFor(User $user): int;
    /**
 * Check if the user has rated this item.
 */
public function hasStarRatingFor(User $user): bool;

/**
 * Get the normalized star rating.
 */
public function getStarRatingFor(User $user): int;

/**
 * Check if this item is highly rated.
 */
public function isHighlyRatedBy(User $user): bool;
}
