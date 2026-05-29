<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Renders the `<starred>` / `<starred2>` wrapper content (favorited artists,
 * albums, and songs). The wrapper element name (`starred` vs `starred2`) is
 * applied by the caller.
 */
final class StarredResource
{
    public const array JSON_STRUCTURE = [
        'artist',
        'album',
        'song',
    ];

    /**
     * @param Collection<int, Artist> $artists
     * @param Collection<int, Album> $albums
     * @param Collection<int, Song> $songs
     *
     * @return array{
     *     artist: list<array<string, mixed>>,
     *     album: list<array<string, mixed>>,
     *     song: list<array<string, mixed>>,
     * }
     */
    public static function toArray(Collection $artists, Collection $albums, Collection $songs, User $user): array
    {
        return [
            'artist' => $artists->map(static fn (Artist $artist) => ArtistResource::toArray($artist, $user))->all(),
            'album' => $albums->map(static fn (Album $album) => AlbumResource::toArray($album, $user))->all(),
            'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
        ];
    }
}
