<?php

namespace App\Repositories;

use App\Models\Artist;
use App\Models\User;
use App\Repositories\Traits\Searchable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

class ArtistRepository extends Repository
{
    use Searchable;

    /** @return Collection|array<array-key, Artist> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        $user ??= auth()->user();

        return Artist::query()
            ->leftJoin('songs', 'artists.id', '=', 'songs.artist_id')
            ->leftJoin('interactions', static function (JoinClause $join) use ($user): void {
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
            ->isStandard()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get('artists.*');
    }

    /** @return Collection|array<array-key, Artist> */
    public function getMany(array $ids, bool $inThatOrder = false): Collection
    {
        $artists = Artist::query()
            ->isStandard()
            ->whereIn('id', $ids)
            ->get();

        return $inThatOrder ? $artists->orderByArray($ids) : $artists;
    }

    public function paginate(): Paginator
    {
        return Artist::query()
            ->isStandard()
            ->orderBy('name')
            ->simplePaginate(21);
    }
}
