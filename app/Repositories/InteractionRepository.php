<?php

namespace App\Repositories;

use App\Models\Interaction;
use App\Models\User;
use App\Repositories\Traits\ByCurrentUser;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class InteractionRepository extends AbstractRepository
{
    use ByCurrentUser;

    public function getModelClass(): string
    {
        return Interaction::class;
    }

    /**
     * Get all songs favorited by a user.
     */
    public function getUserFavorites(User $user): Collection
    {
        return $this->model->where([
            'user_id' => $user->id,
            'liked' => true,
        ])
            ->with('song')
            ->get()
            ->pluck('song');
    }

    /**
     * @return Interaction[]
     */
    public function getRecentlyPlayed(User $user, ?int $count = null): array
    {
        /** @var Builder $query */
        $query = $this->model
            ->where('user_id', $user->id)
            ->where('play_count', '>', 0)
            ->orderBy('updated_at', 'DESC');

        if ($count) {
            $query = $query->take($count);
        }

        return $query
            ->get()
            ->pluck('song_id')
            ->all();
    }
}
