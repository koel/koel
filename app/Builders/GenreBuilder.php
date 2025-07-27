<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GenreBuilder extends Builder
{
    public function accessibleBy(User $user): self
    {
        if (License::isCommunity()) {
            // With the Community license, all genres are accessible by all users.
            return $this;
        }

        return $this->whereHas('songs', static fn (SongBuilder $query) => $query->setScopedUser($user)->accessible()); //@phpstan-ignore-line
    }
}
