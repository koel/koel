<?php

namespace App\Repositories;

use App\Models\Interaction;
use App\Models\User;
use App\Repositories\Traits\ByCurrentUser;
use Illuminate\Support\Collection;

class InteractionRepository extends Repository
{
    use ByCurrentUser;

    /** @return Collection|array<Interaction> */
    public function getUserFavorites(User $user): Collection
    {
        return $this->model
            ->newQuery()
            ->where([
                'user_id' => $user->id,
                'liked' => true,
            ])
            ->with('song')
            ->pluck('song');
    }

    /** @return array<Interaction> */
    public function getRecentlyPlayed(User $user, ?int $count = null): array
    {
        $query = $this->model
            ->newQuery()
            ->where('user_id', $user->id)
            ->where('play_count', '>', 0)
            ->latest('last_played_at');

        if ($count) {
            $query = $query->take($count);
        }

        return $query->pluck('song_id')->all();
    }
}
