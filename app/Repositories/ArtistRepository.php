<?php

namespace App\Repositories;

use App\Models\Artist;
use App\Models\User;
use App\Repositories\Traits\Searchable;
use Illuminate\Database\Eloquent\Collection;

class ArtistRepository extends Repository
{
    use Searchable;

    /** @return Collection|array<array-key, Artist> */
    public function getMostPlayed(int $count = 6, ?User $scopedUser = null): Collection
    {
        return Artist::withMeta($scopedUser ?? $this->auth->user())
            ->isStandard()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    public function getOne(int $id, ?User $scopedUser = null): Artist
    {
        return Artist::withMeta($scopedUser ?? $this->auth->user())
            ->where('artists.id', $id)
            ->first();
    }

    /** @return Collection|array<array-key, Artist> */
    public function getByIds(array $ids, ?User $scopedUser = null): Collection
    {
        return Artist::withMeta($scopedUser ?? $this->auth->user())
            ->isStandard()
            ->whereIn('artists.id', $ids)
            ->get();
    }
}
