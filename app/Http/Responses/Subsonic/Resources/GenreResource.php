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
    public static function toArray(GenreSummary $genre, int $albumCount): array
    {
        return [
            'songCount' => $genre->songCount,
            'albumCount' => $albumCount,
            'value' => $genre->name,
        ];
    }
}
