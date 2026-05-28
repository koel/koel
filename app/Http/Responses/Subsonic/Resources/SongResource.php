<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Song;
use App\Models\User;

final class SongResource
{
    /**
     * @return array{
     *     id: string,
     *     parent: string,
     *     isDir: bool,
     *     title: string,
     *     album: ?string,
     *     artist: ?string,
     *     track: ?int,
     *     year: ?int,
     *     genre: ?string,
     *     coverArt: ?string,
     *     size: ?int,
     *     contentType: ?string,
     *     suffix: ?string,
     *     duration: int,
     *     created: string,
     *     albumId: string,
     *     artistId: string,
     *     type: string,
     *     discNumber: ?int,
     *     isVideo: bool,
     *     userRating: ?int,
     * }
     */
    public static function toArray(Song $song, User $user): array
    {
        return [
            'id' => $song->id,
            'parent' => $song->album_id,
            'isDir' => false,
            'title' => $song->title,
            'album' => $song->album_name,
            'artist' => $song->artist_name,
            'track' => $song->track ?: null,
            'year' => $song->year,
            'genre' => $song->genre ?: null,
            'coverArt' => $song->album?->cover ? $song->album_id : null,
            'size' => $song->file_size,
            'contentType' => $song->mime_type,
            'suffix' => pathinfo($song->path, PATHINFO_EXTENSION) ?: null,
            'duration' => (int) round($song->length),
            'created' => $song->created_at->toIso8601String(),
            'albumId' => $song->album_id,
            'artistId' => $song->artist_id,
            'type' => 'music',
            'discNumber' => $song->disc ?: null,
            'isVideo' => false,
            'userRating' => $song->getRatingFor($user) ?: null,
        ];
    }
}
