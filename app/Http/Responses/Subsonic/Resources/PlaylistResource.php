<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Playlist;

final class PlaylistResource
{
    /** Keys always present after stripNulls. Nullable `coverArt` is not listed. */
    public const array JSON_STRUCTURE = [
        'id',
        'name',
        'comment',
        'owner',
        'public',
        'songCount',
        'duration',
        'created',
        'changed',
    ];

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     comment: string,
     *     owner: string,
     *     public: bool,
     *     songCount: int,
     *     duration: int,
     *     created: string,
     *     changed: string,
     *     coverArt: ?string,
     * }
     */
    public static function toArray(Playlist $playlist): array
    {
        return [
            'id' => $playlist->id,
            'name' => $playlist->name,
            'comment' => (string) $playlist->description,
            'owner' => $playlist->owner->name,
            'public' => false,
            'songCount' => $playlist->playables_count ?? 0,
            'duration' => (int) round($playlist->playables_sum_length ?? 0),
            'created' => $playlist->created_at->toIso8601String(),
            'changed' => $playlist->updated_at->toIso8601String(),
            'coverArt' => $playlist->cover ? $playlist->id : null,
        ];
    }
}
