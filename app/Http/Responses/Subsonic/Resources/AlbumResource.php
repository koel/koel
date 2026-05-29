<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Album;
use App\Models\User;

final class AlbumResource
{
    /** Keys always present after stripNulls. Nullable fields (coverArt, year, userRating) are not listed. */
    public const array JSON_STRUCTURE = [
        'id',
        'name',
        'artist',
        'artistId',
        'songCount',
        'duration',
        'created',
    ];

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     artist: string,
     *     artistId: string,
     *     coverArt: ?string,
     *     songCount: int,
     *     duration: int,
     *     created: string,
     *     year: ?int,
     *     userRating: ?int,
     * }
     */
    public static function toArray(Album $album, User $user): array
    {
        return [
            'id' => $album->id,
            'name' => $album->name,
            'artist' => $album->artist_name,
            'artistId' => $album->artist_id,
            'coverArt' => $album->cover ? $album->id : null,
            'songCount' => $album->songs_count ?? 0,
            'duration' => (int) round($album->songs_sum_length ?? 0),
            'created' => $album->created_at->toIso8601String(),
            'year' => $album->year,
            'userRating' => $album->getRatingFor($user) ?: null,
        ];
    }
}
