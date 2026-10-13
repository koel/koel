<?php

namespace App\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 *
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

            $join->on('favorites.favoriteable_id', $joinColumn)->where(
                'favorites.favoriteable_type',
                $this->getModel()->getMorphClass(),
            )->where('favorites.user_id', $this->user->id);
        });

        $this->addSelect(DB::raw(
            '(CASE WHEN favorites.created_at IS NULL THEN false ELSE true END) AS favorite',
        ))->addSelect('favorites.created_at as favorited_at');

        return $this;
    }

    protected function withRatingJoin(): static
    {
        throw_unless($this->user, new LogicException('User must be set to query ratings.'));

        $ratingsAlias = $this->model->getMorphClass() . '_ratings';

        $this->leftJoin("ratings as $ratingsAlias", function (JoinClause $join) use ($ratingsAlias): void {
            $join->on("$ratingsAlias.rateable_id", $this->model->getQualifiedKeyName())->where(
                "$ratingsAlias.rateable_type",
                $this->model->getMorphClass(),
            )->where("$ratingsAlias.user_id", $this->user->id);
        })->addSelect(DB::raw("(COALESCE($ratingsAlias.rating, 0)) as rating"));

        if ($this->getQuery()->groups) {
            $this->groupBy("$ratingsAlias.rating");
        }

        return $this;
    }

    protected function withRatingSubquery(): static
    {
        throw_unless($this->user, new LogicException('User must be set to query ratings.'));

        $this->addSelect([
            'rating' => DB::table('ratings')
                ->where('rateable_type', $this->model->getMorphClass())
                ->where('user_id', $this->user->id)
                ->whereColumn('rateable_id', $this->model->getQualifiedKeyName())
                ->selectRaw('COALESCE(MAX(rating), 0)'),
        ]);

        return $this;
    }
}
