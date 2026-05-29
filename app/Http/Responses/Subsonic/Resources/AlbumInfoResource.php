<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Values\Album\AlbumInformation;

/**
 * Renders the body of the `<albumInfo>` element returned by both `getAlbumInfo`
 * (v1) and `getAlbumInfo2` (v2). Per spec the wrapper name is identical for
 * both versions; the body is unchanged.
 */
final class AlbumInfoResource
{
    /** Keys always present after stripNulls. Nullable fields (notes, image URLs) are not listed. */
    public const array JSON_STRUCTURE = [
        'lastFmUrl',
    ];

    /**
     * @return array{
     *     notes: ?string,
     *     lastFmUrl: string,
     *     smallImageUrl: ?string,
     *     mediumImageUrl: ?string,
     *     largeImageUrl: ?string,
     * }
     */
    public static function toArray(AlbumInformation $info): array
    {
        $imageUrl = $info->cover ?: null;

        return [
            'notes' => $info->wiki['summary'] ?: null,
            'lastFmUrl' => $info->url,
            'smallImageUrl' => $imageUrl,
            'mediumImageUrl' => $imageUrl,
            'largeImageUrl' => $imageUrl,
        ];
    }
}
