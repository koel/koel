<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\User;
use App\Repositories\Traits\Searchable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

class AlbumRepository extends Repository
{
    use Searchable;

    public function getOne(int $id, ?User $scopedUser = null): Album
    {
        return Album::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->where('albums.id', $id)
            ->first();
    }

    /** @return Collection|array<array-key, Album> */
    public function getRecentlyAdded(int $count = 6, ?User $scopedUser = null): Collection
    {
        return Album::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->isStandard()
            ->latest('albums.created_at')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Album> */
    public function getMostPlayed(int $count = 6, ?User $scopedUser = null): Collection
    {
        return Album::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->isStandard()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Album> */
    public function getByIds(array $ids, ?User $scopedUser = null): Collection
    {
        return Album::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->whereIn('albums.id', $ids)
            ->get();
    }

    public function paginate(?User $scopedUser = null): Paginator
    {
        return Album::query()
            ->withMeta($scopedUser ?? $this->auth->user())
            ->isStandard()
            ->orderBy('albums.name')
            ->simplePaginate(21);
    }
}
