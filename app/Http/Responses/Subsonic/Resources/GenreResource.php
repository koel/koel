<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Values\GenreSummary;

final class GenreResource
{
    /** @return array<string, mixed> */
    public static function toArray(GenreSummary $genre): array
    {
        return [
            'songCount' => $genre->songCount,
            'albumCount' => 0,
            'value' => $genre->name,
        ];
    }
}
