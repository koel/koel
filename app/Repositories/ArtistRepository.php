<?php

namespace App\Repositories;

use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

class ArtistRepository extends Repository
{
    /** @return Collection|array<array-key, Artist> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        /** @var ?User $user */
        $user ??= auth()->user();

        $query = Artist::query()
            ->isStandard()
            ->accessibleBy($user);

        if (License::isCommunity()) {
            // if the license is Plus, accessibleBy() would have already joined the songs table
            // and we don't want to join it twice
            $query->leftJoin('songs', 'artists.id', 'songs.artist_id');
        }

        return $query->join('interactions', static function (JoinClause $join) use ($user): void {
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
    public function getMany(array $ids, bool $inThatOrder = false, ?User $user = null): Collection
    {
        $artists = Artist::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->whereIn('artists.id', $ids)
            ->groupBy('artists.id')
            ->distinct()
            ->get('artists.*');

        return $inThatOrder ? $artists->orderByArray($ids) : $artists;
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
