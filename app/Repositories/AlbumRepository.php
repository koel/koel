<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\Traits\Searchable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class AlbumRepository extends Repository
{
    use Searchable;

    /** @return Collection|array<array-key, Album> */
    public function getRecentlyAdded(int $count = 6): Collection
    {
        return Album::query()
            ->isStandard()
            ->latest('created_at')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Album> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Album::query()
            ->leftJoin('songs', 'albums.id', 'songs.album_id')
            ->leftJoin('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('songs.id', 'interactions.song_id')->where('interactions.user_id', $user->id);
            })
            ->isStandard()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get('albums.*');
    }

    /** @return Collection|array<array-key, Album> */
    public function getByArtist(Artist $artist): Collection
    {
        return Album::query()
            ->where('artist_id', $artist->id)
            ->orWhereIn('id', $artist->songs()->pluck('album_id'))
            ->orderBy('name')
            ->get();
    }

    public function paginate(): Paginator
    {
        return Album::query()
            ->isStandard()
            ->orderBy('name')
            ->simplePaginate(21);
    }
}
