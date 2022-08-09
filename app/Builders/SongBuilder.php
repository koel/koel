<?php

namespace App\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class SongBuilder extends Builder
{
    public function inDirectory(string $path): static
    {
        // Make sure the path ends with a directory separator.
        $path = rtrim(trim($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $this->where('path', 'LIKE', "$path%");
    }

    public function withMeta(User $user): static
    {
        return $this
            ->with('artist', 'album', 'album.artist')
            ->leftJoin('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('interactions.song_id', '=', 'songs.id')->where('interactions.user_id', $user->id);
            })
            ->join('albums', 'songs.album_id', '=', 'albums.id')
            ->join('artists', 'songs.artist_id', '=', 'artists.id')
            ->select(
                'songs.*',
                'albums.name',
                'artists.name',
                'interactions.liked',
                'interactions.play_count'
            );
    }

    public function hostedOnS3(): static
    {
        return $this->where('path', 'LIKE', 's3://%');
    }
}
