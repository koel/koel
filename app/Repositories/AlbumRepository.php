<?php

namespace App\Repositories;

use App\Builders\AlbumBuilder;
use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class AlbumRepository extends Repository
{
    /** @return Collection|array<array-key, Album> */
    public function getRecentlyAdded(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->isStandard()
            ->accessibleBy($user ?? $this->auth->user())
            ->groupBy('albums.id')
            ->distinct()
            ->latest('albums.created_at')
            ->limit($count)
            ->get('albums.*');
    }

    /** @return Collection|array<array-key, Album> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        /** @var ?User $user */
        $user ??= $this->auth->user();

        return Album::query()
            ->isStandard()
            ->accessibleBy($user)
            ->unless(
                License::isPlus(), // if the license is Plus, accessibleBy() would have already joined with `songs`
                static fn (AlbumBuilder $query) => $query->leftJoin('songs', 'albums.id', 'songs.album_id')
            )
            ->join('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('songs.id', 'interactions.song_id')->where('interactions.user_id', $user->id);
            })
            ->groupBy('albums.id')
            ->distinct()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get('albums.*');
    }

    /** @return Collection|array<array-key, Album> */
    public function getMany(array $ids, bool $inThatOrder = false, ?User $user = null): Collection
    {
        $albums = Album::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->whereIn('albums.id', $ids)
            ->groupBy('albums.id')
            ->distinct()
            ->get('albums.*');

        return $inThatOrder ? $albums->orderByArray($ids) : $albums;
    }

    /** @return Collection|array<array-key, Album> */
    public function getByArtist(Artist $artist, ?User $user = null): Collection
    {
        return Album::query()
            ->accessibleBy($user ?? $this->auth->user())
            ->where('albums.artist_id', $artist->id)
            ->orWhereIn('albums.id', $artist->songs()->pluck('album_id'))
            ->orderBy('albums.name')
            ->groupBy('albums.id')
            ->distinct()
            ->get('albums.*');
    }

    public function paginate(?User $user = null): Paginator
    {
        return Album::query()
            ->accessibleBy($user ?? $this->auth->user())
            ->isStandard()
            ->orderBy('albums.name')
            ->groupBy('albums.id')
            ->distinct()
            ->select('albums.*')
            ->simplePaginate(21);
    }
}
