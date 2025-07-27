<?php

namespace App\Models\Concerns;

use App\Models\Favorite;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait MorphsToFavorites
{
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoriteable');
    }
}
