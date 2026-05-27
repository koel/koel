<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Artist;

final class ArtistResource
{
    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     coverArt: ?string,
     *     albumCount: int,
     * }
     */
    public static function toArray(Artist $artist): array
    {
        return [
            'id' => $artist->id,
            'name' => $artist->name,
            'coverArt' => $artist->image ? $artist->id : null,
            'albumCount' => $artist->albums_count ?? 0,
        ];
    }
}
