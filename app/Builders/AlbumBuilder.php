<?php

namespace App\Builders;

use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class AlbumBuilder extends Builder
{
    public function isStandard(): static
    {
        return $this->whereNot('albums.id', Album::UNKNOWN_ID);
    }

    public function withMeta(User $user): static
    {
        return $this->with('artist')
            ->leftJoin('songs', 'albums.id', '=', 'songs.album_id')
            ->leftJoin('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('songs.id', '=', 'interactions.song_id')->where('interactions.user_id', $user->id);
            })
            ->groupBy('albums.id')
            ->select(
                'albums.*',
                DB::raw('CAST(SUM(interactions.play_count) AS UNSIGNED) AS play_count')
            )
            ->withCount('songs AS song_count')
            ->withSum('songs AS length', 'length');
    }
}
