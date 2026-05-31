<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Song as Episode;

final class PodcastEpisodeResource
{
    /** Keys always present after stripNulls. Nullable fields (description, coverArt, contentType, suffix, size) are not listed because they may be absent. */
    public const array JSON_STRUCTURE = [
        'id',
        'streamId',
        'channelId',
        'parent',
        'isDir',
        'title',
        'duration',
        'created',
        'publishDate',
        'status',
        'type',
        'isVideo',
    ];

    /**
     * @return array{
     *     id: string,
     *     streamId: string,
     *     channelId: string,
     *     parent: string,
     *     isDir: bool,
     *     title: string,
     *     description: ?string,
     *     coverArt: ?string,
     *     size: ?int,
     *     contentType: ?string,
     *     suffix: ?string,
     *     duration: int,
     *     created: string,
     *     publishDate: string,
     *     status: string,
     *     type: string,
     *     isVideo: bool,
     * }
     */
    public static function toArray(Episode $episode): array
    {
        $publishDate = $episode->episode_metadata?->pubDate
            ? $episode->episode_metadata->pubDate->format('c')
            : $episode->created_at->toIso8601String();

        return [
            'id' => $episode->id,
            'streamId' => $episode->id,
            'channelId' => $episode->podcast_id,
            'parent' => $episode->podcast_id,
            'isDir' => false,
            'title' => $episode->title,
            'description' => $episode->episode_metadata?->description,
            'coverArt' => $episode->podcast_id,
            'size' => $episode->file_size,
            'contentType' => $episode->mime_type,
            'suffix' => pathinfo($episode->path, PATHINFO_EXTENSION) ?: null,
            'duration' => (int) round($episode->length),
            'created' => $episode->created_at->toIso8601String(),
            'publishDate' => $publishDate,
            'status' => 'completed',
            'type' => 'podcast',
            'isVideo' => false,
        ];
    }
}
