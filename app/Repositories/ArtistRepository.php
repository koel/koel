<?php

namespace App\Repositories;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/** @extends Repository<Artist> */
class ArtistRepository extends Repository
{
    /** @return Collection|array<array-key, Artist> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        $user ??= auth()->user();

        return Artist::query()
            ->isStandard()
            ->accessibleBy($user)
            ->leftJoin('songs', 'artists.id', 'songs.artist_id')
            ->join('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('interactions.song_id', '=', 'songs.id')->where('interactions.user_id', $user->id);
            })
            ->select('artists.*', DB::raw('SUM(interactions.play_count) as play_count'))
            ->groupBy('artists.id')
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Artist> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $artists = Artist::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->whereIn('artists.id', $ids)
            ->distinct()
            ->get('artists.*');

        return $preserveOrder ? $artists->orderByArray($ids) : $artists;
    }

    public function getForListing(string $sortColumn, string $sortDirection, ?User $user = null): Paginator
    {
        return Artist::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->sort($sortColumn, $sortDirection)
            ->distinct()
            ->orderBy('artists.name')
            ->select('artists.*')
            ->simplePaginate(21);
    }
}
