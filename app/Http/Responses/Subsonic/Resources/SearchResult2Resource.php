<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Renders the `<searchResult2>` wrapper content for the Subsonic v2 `search2`
 * endpoint. Albums use the Child shape (`AlbumChildResource`) rather than the
 * AlbumID3 shape used by `search3`; songs use the song-Child shape directly
 * from `SongResource`.
 */
final class SearchResult2Resource
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
            'album' => $albums->map(AlbumChildResource::toArray(...))->all(),
            'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
        ];
    }

    /**
     * @return array{artist: array<empty>, album: array<empty>, song: array<empty>}
     */
    public static function empty(): array
    {
        return ['artist' => [], 'album' => [], 'song' => []];
    }
}
