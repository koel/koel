<?php

namespace App\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * @property ?User $user
 */
abstract class FavoriteableBuilder extends Builder
{
    /**
     * @param bool $favoritesOnly Whether to query the user's favorites only.
     *                            If false (default), queries will return all records
     *                            with a 'liked' column indicating whether the user has favorited each record.
     */
    public function withFavoriteStatus(bool $favoritesOnly = false): static
    {
        $joinMethod = $favoritesOnly ? 'join' : 'leftJoin';

        $this->$joinMethod('favorites', function (JoinClause $join): void {
            $joinColumn = $this->model->getTable() . '.' . $this->model->getKeyName();

            $join->on('favorites.favoriteable_id', $joinColumn)
                ->where('favorites.favoriteable_type', $this->getModel()->getMorphClass())
                ->where('favorites.user_id', $this->user->id);
        });

        $this->addSelect(DB::raw('CASE WHEN favorites.created_at IS NULL THEN false ELSE true END AS favorite'));

        return $this;
    }
}
