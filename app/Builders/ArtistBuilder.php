<?php

namespace App\Builders;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ArtistBuilder extends Builder
{
    public function isStandard(): static
    {
        return $this->whereNotIn('artists.id', [Artist::UNKNOWN_ID, Artist::VARIOUS_ID]);
    }

    public function withMeta(User $user): static
    {
        $integer = $this->integerCastType();

        return $this->leftJoin('songs', 'artists.id', '=', 'songs.artist_id')
            ->leftJoin('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('interactions.song_id', '=', 'songs.id')->where('interactions.user_id', $user->id);
            })
            ->groupBy('artists.id')
            ->select([
                'artists.*',
                DB::raw("CAST(SUM(interactions.play_count) AS $integer) AS play_count"),
                DB::raw('COUNT(DISTINCT songs.album_id) AS album_count'),
            ])
            ->withCount('songs AS song_count')
            ->withSum('songs AS length', 'length');
    }
}
