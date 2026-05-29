<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Song;

/**
 * Renders the body of the v1 `<lyrics>` element returned by `getLyrics`.
 * The newer OpenSubsonic `<lyricsList>` returned by `getLyricsBySongId` uses
 * a different (structured) shape and is rendered inline by its controller.
 */
final class LyricsResource
{
    public const array JSON_STRUCTURE = [
        'artist',
        'title',
        'value',
    ];

    /**
     * @return array{
     *     artist: string,
     *     title: string,
     *     value: string,
     * }
     */
    public static function toArray(Song $song, string $lyrics): array
    {
        return [
            'artist' => $song->artist_name,
            'title' => $song->title,
            'value' => $lyrics,
        ];
    }
}
