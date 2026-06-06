<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Renders the `<directory>` element returned by `getMusicDirectory`. koel maps
 * the Subsonic folder model onto its catalog: an artist directory contains its
 * albums as Child(isDir=true) entries; an album directory contains its songs
 * as Child(isDir=false) entries.
 */
final class DirectoryResource
{
    public const array JSON_STRUCTURE = [
        'id',
        'name',
        'child',
    ];

    /**
     * @param Collection<int, Album> $albums
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     child: list<array<string, mixed>>,
     * }
     */
    public static function forArtist(Artist $artist, Collection $albums): array
    {
        return [
            'id' => $artist->id,
            'name' => $artist->name,
            'child' => $albums->map(AlbumChildResource::toArray(...))->all(),
        ];
    }

    /**
     * @param Collection<int, Song> $songs
     *
     * @return array{
     *     id: string,
     *     parent: string,
     *     name: string,
     *     child: list<array<string, mixed>>,
     * }
     */
    public static function forAlbum(Album $album, Collection $songs, User $user): array
    {
        return [
            'id' => $album->id,
            'parent' => $album->artist_id,
            'name' => $album->name,
            'child' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
        ];
    }
}
