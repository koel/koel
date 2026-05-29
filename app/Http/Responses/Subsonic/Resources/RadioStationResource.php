<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\RadioStation;

final class RadioStationResource
{
    /** Keys always present after stripNulls. Nullable `homepageUrl` is not listed. */
    public const array JSON_STRUCTURE = [
        'id',
        'name',
        'streamUrl',
    ];

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     streamUrl: string,
     *     homepageUrl: ?string,
     * }
     */
    public static function toArray(RadioStation $station): array
    {
        return [
            'id' => $station->id,
            'name' => $station->name,
            'streamUrl' => $station->url,
            'homepageUrl' => $station->homepage_url,
        ];
    }
}
