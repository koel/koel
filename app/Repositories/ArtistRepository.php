<?php

namespace App\Repositories;

use App\Builders\ArtistBuilder;
use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection as BaseCollection;

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
            ->unless(
                License::isPlus(), // if the license is Plus, accessibleBy() would have already joined with `songs`
                static fn (ArtistBuilder $query) => $query->leftJoin('songs', 'artists.id', 'songs.artist_id')
            )
            ->join('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('interactions.song_id', '=', 'songs.id')->where('interactions.user_id', $user->id);
            })
            ->groupBy([
                'artists.id',
                'play_count',
                'artists.name',
                'artists.image',
                'artists.created_at',
                'artists.updated_at',
            ])
            ->distinct()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get('artists.*');
    }

    /** @return Collection|array<array-key, Artist> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection|BaseCollection
    {
        $artists = Artist::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->whereIn('artists.id', $ids)
            ->groupBy('artists.id')
            ->distinct()
            ->get('artists.*');

        return $preserveOrder ? $artists->orderByArray($ids) : $artists;
    }

    public function paginate(?User $user = null): Paginator
    {
        return Artist::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->groupBy('artists.id')
            ->distinct()
            ->orderBy('artists.name')
            ->select('artists.*')
            ->simplePaginate(21);
    }
}
