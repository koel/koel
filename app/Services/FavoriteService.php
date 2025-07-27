<?php

namespace App\Services;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongFavoriteToggled;
use App\Models\Contracts\Favoriteable;
use App\Models\Favorite;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FavoriteService
{
    public function toggleFavorite(Favoriteable|Model $favoriteable, User $user): ?Favorite
    {
        $favorite = $favoriteable->favorites()->where('user_id', $user->id)->delete() === 0
            ? $favoriteable->favorites()->create(['user_id' => $user->id])
            : null;

        if ($favoriteable instanceof Song) {
            event(new SongFavoriteToggled($favoriteable, (bool) $favorite, $user));
        }

        return $favorite;
    }

    /**
     * Batch favorite multiple entities.
     *
     * @param Collection<int, Model&Favoriteable> $entities
     */
    public function batchFavorite(Collection $entities, User $user): void
    {
        $favorites = [];

        foreach ($entities as $entity) {
            if (!$entity instanceof Favoriteable) {
                throw new InvalidArgumentException('Entity must implement Favoriteable interface.');
            }

            $favorites[] = [
                'user_id' => $user->id,
                'favoriteable_type' => $entity->getMorphClass(),
                'favoriteable_id' => $entity->getKey(),
                'created_at' => now(),
            ];
        }

        Favorite::query()->upsert($favorites, ['favoriteable_type', 'favoriteable_id', 'user_id']);

        $songs = $entities->filter(static fn (Model $entity) => $entity instanceof Song);

        if ($songs->isNotEmpty()) {
            event(new MultipleSongsLiked($songs, $user));
        }
    }

    /**
     * Batch undo favorite for multiple entities.
     *
     * @param Collection<int, Model&Favoriteable> $entities
     */
    public function batchUndoFavorite(Collection $entities, User $user): void
    {
        $grouped = $entities->groupBy(static fn (Model $entity) => $entity->getMorphClass());

        DB::transaction(static function () use ($grouped, $user): void {
            foreach ($grouped as $type => $items) {
                Favorite::query()
                    ->whereBelongsTo($user)
                    ->where('favoriteable_type', $type)
                    ->whereIn('favoriteable_id', $items->pluck('id'))
                    ->delete();
            }
        });

        $songs = $entities->filter(static fn (Model $entity) => $entity instanceof Song);

        if ($songs->isNotEmpty()) {
            event(new MultipleSongsUnliked($songs, $user));
        }
    }
}
