<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Values\Artist\ArtistInformation;

/**
 * Renders the body of the `<artistInfo>` (v1) / `<artistInfo2>` (v2) elements.
 * The wrapper element name is applied by the caller — the spec uses different
 * names per version but the same field set.
 */
final class ArtistInfoResource
{
    /** Keys always present after stripNulls. Nullable fields (biography, image URLs) are not listed. */
    public const array JSON_STRUCTURE = [
        'lastFmUrl',
    ];

    /**
     * @return array{
     *     biography: ?string,
     *     lastFmUrl: string,
     *     smallImageUrl: ?string,
     *     mediumImageUrl: ?string,
     *     largeImageUrl: ?string,
     * }
     */
    public static function toArray(ArtistInformation $info): array
    {
        $imageUrl = $info->image ?: null;

        return [
            'biography' => $info->bio['summary'] ?: null,
            'lastFmUrl' => $info->url,
            'smallImageUrl' => $imageUrl,
            'mediumImageUrl' => $imageUrl,
            'largeImageUrl' => $imageUrl,
        ];
    }
}
