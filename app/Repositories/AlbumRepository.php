<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * @extends Repository<Album>
 */
class AlbumRepository extends Repository
{
    /** @return Collection|array<array-key, Album> */
    public function getRecentlyAdded(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->isStandard()
            ->accessibleBy($user ?? $this->auth->user())
            ->distinct()
            ->latest('albums.created_at')
            ->limit($count)
            ->get('albums.*');
    }

    /** @return Collection|array<array-key, Album> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Album::query()
            ->isStandard()
            ->accessibleBy($user)
            ->leftJoin('songs', 'albums.id', 'songs.album_id')
            ->join('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('songs.id', 'interactions.song_id')->where('interactions.user_id', $user->id);
            })
            ->select('albums.*', DB::raw('SUM(interactions.play_count) as play_count'))
            ->groupBy('albums.id')
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Album> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $albums = Album::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->whereIn('albums.id', $ids)
            ->distinct()
            ->get('albums.*');

        return $preserveOrder ? $albums->orderByArray($ids) : $albums;
    }

    /** @return Collection|array<array-key, Album> */
    public function getByArtist(Artist $artist, ?User $user = null): Collection
    {
        return Album::query()
            ->accessibleBy($user ?? $this->auth->user())
            ->where('albums.artist_id', $artist->id)
            ->orWhereIn('albums.id', $artist->songs()->pluck('album_id'))
            ->orderBy('albums.name')
            ->distinct()
            ->get('albums.*');
    }

    public function getForListing(string $sortColumn, string $sortDirection, ?User $user = null): Paginator
    {
        return Album::query()
            ->accessibleBy($user ?? $this->auth->user())
            ->isStandard()
            ->sort($sortColumn, $sortDirection)
            ->distinct()
            ->select('albums.*', 'artists.name as artist_name')
            ->simplePaginate(21);
    }
}
