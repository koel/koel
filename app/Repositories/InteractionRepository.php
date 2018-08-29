<?php

namespace App\Repositories;

use App\Models\Interaction;
use App\Models\User;
use App\Repositories\Traits\ByCurrentUser;
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
            'like' => true,
        ])
            ->with('song')
            ->get()
            ->pluck('song');
    }
}
