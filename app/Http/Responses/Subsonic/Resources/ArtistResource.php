<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Artist;
use App\Models\User;

final class ArtistResource
{
    /** Keys always present after stripNulls. Nullable fields (coverArt, userRating) are not listed. */
    public const array JSON_STRUCTURE = [
        'id',
        'name',
        'albumCount',
    ];

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     coverArt: ?string,
     *     albumCount: int,
     *     userRating: ?int,
     * }
     */
    public static function toArray(Artist $artist, User $user): array
    {
        return [
            'id' => $artist->id,
            'name' => $artist->name,
            'coverArt' => $artist->image ? $artist->id : null,
            'albumCount' => $artist->albums_count ?? 0,
            'userRating' => $artist->getRatingFor($user) ?: null,
        ];
    }
}
