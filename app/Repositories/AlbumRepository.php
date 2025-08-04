<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<Album>
 * @implements ScoutableRepository<Album>
 */
class AlbumRepository extends Repository implements ScoutableRepository
{
    /**
     * @param string $id
     */
    public function getOne($id, ?User $user = null): Album
    {
        return Album::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->findOrFail($id);
    }

    /** @return Collection|array<array-key, Album> */
    public function getRecentlyAdded(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->latest()
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Album> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user(), includePlayCount: true)
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Album> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $albums = Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->whereIn('albums.id', $ids)
            ->get();

        return $preserveOrder ? $albums->orderByArray($ids) : $albums;
    }

    /** @return Collection|array<array-key, Album> */
    public function getByArtist(Artist $artist, ?User $user = null): Collection
    {
        return Album::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->where(static function (Builder $query) use ($artist): void {
                $query->whereBelongsTo($artist)
                    ->orWhereHas('songs', static function (Builder $songQuery) use ($artist): void {
                        $songQuery->whereBelongsTo($artist);
                    });
            })
            ->orderBy('albums.name')
            ->get();
    }

    public function getForListing(
        string $sortColumn,
        string $sortDirection,
        bool $favoritesOnly = false,
        ?User $user = null,
    ): Paginator {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user(), favoritesOnly: $favoritesOnly)
            ->sort($sortColumn, $sortDirection)
            ->simplePaginate(21);
    }

    /** @return Collection<Album>|array<array-key, Album> */
    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Album::search($keywords)->get()->take($limit)->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
