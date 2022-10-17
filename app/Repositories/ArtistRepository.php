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
            ->groupBy('artists.id')
            ->isStandard()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get('artists.*');
    }

    public function getOne(int $id): Artist
    {
        return Artist::query()->find($id);
    }

    /** @return Collection|array<array-key, Artist> */
    public function getByIds(array $ids): Collection
    {
        return Artist::query()
            ->isStandard()
            ->whereIn('id', $ids)
            ->get();
    }

    public function paginate(): Paginator
    {
        return Artist::query()
            ->isStandard()
            ->orderBy('name')
            ->simplePaginate(21);
    }
}
