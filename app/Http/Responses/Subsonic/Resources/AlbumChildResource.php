<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Album;

/**
 * Renders an Album as a Subsonic `<child isDir="true">` element, for use inside
 * `<directory>` responses (getMusicDirectory). The full AlbumID3 shape lives in
 * {@see AlbumResource}; this resource only emits the Child fields the spec
 * expects for a directory entry.
 */
final class AlbumChildResource
{
    /** Keys always present after stripNulls. Nullable `coverArt` / `year` are not listed. */
    public const array JSON_STRUCTURE = [
        'id',
        'parent',
        'isDir',
        'title',
        'artist',
        'artistId',
        'created',
    ];

    /**
     * @return array{
     *     id: string,
     *     parent: string,
     *     isDir: bool,
     *     title: string,
     *     artist: string,
     *     artistId: string,
     *     coverArt: ?string,
     *     year: ?int,
     *     created: string,
     * }
     */
    public static function toArray(Album $album): array
    {
        return [
            'id' => $album->id,
            'parent' => $album->artist_id,
            'isDir' => true,
            'title' => $album->name,
            'artist' => $album->artist_name,
            'artistId' => $album->artist_id,
            'coverArt' => $album->cover ? $album->id : null,
            'year' => $album->year,
            'created' => $album->created_at->toIso8601String(),
        ];
    }
}
