<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Song;
use App\Values\ParsedLyrics;

/**
 * Renders a single `<structuredLyrics>` entry of the OpenSubsonic `<lyricsList>`
 * returned by `getLyricsBySongId`.
 */
final class StructuredLyricsResource
{
    /**
     * @return array{
     *     displayArtist: string,
     *     displayTitle: string,
     *     lang: string,
     *     offset: int,
     *     synced: bool,
     *     line: list<array{start?: int, value: string}>,
     * }
     */
    public static function toArray(Song $song, ParsedLyrics $lyrics): array
    {
        return [
            'displayArtist' => $song->artist_name,
            'displayTitle' => $song->title,
            'lang' => 'und',
            'offset' => $lyrics->offset,
            'synced' => $lyrics->synced,
            'line' => $lyrics->lines,
        ];
    }
}
