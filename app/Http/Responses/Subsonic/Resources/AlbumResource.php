<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Album;

final class AlbumResource
{
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
     * }
     */
    public static function toArray(Album $album): array
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
        ];
    }
}
