<?php

namespace App\Repositories;

use App\Models\Interaction;
use App\Models\User;
use Illuminate\Support\Collection;

class InteractionRepository extends Repository
{
    /** @return Collection|array<Interaction> */
    public function getUserFavorites(User $user): Collection
    {
        return Interaction::query()
            ->where([
                'user_id' => $user->id,
                'liked' => true,
            ])
            ->with('song')
            ->pluck('song');
    }
}
