<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Values\GenreSummary;

final class GenreResource
{
    public const array JSON_STRUCTURE = [
        'songCount',
        'albumCount',
        'value',
    ];

    /**
     * @return array{
     *     songCount: int,
     *     albumCount: int,
     *     value: string,
     * }
     */
    public static function toArray(GenreSummary $genre): array
    {
        return [
            'songCount' => $genre->songCount,
            'albumCount' => 0,
            'value' => $genre->name,
        ];
    }
}
