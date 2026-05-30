<?php

namespace App\Http\Responses\Subsonic\Resources;

use App\Models\Podcast;

final class PodcastChannelResource
{
    /** Keys always present after stripNulls. Nullable fields (description, originalImageUrl) are not listed. */
    public const array JSON_STRUCTURE = [
        'id',
        'url',
        'title',
        'coverArt',
        'status',
    ];

    /**
     * @return array{
     *     id: string,
     *     url: string,
     *     title: string,
     *     description: ?string,
     *     coverArt: ?string,
     *     originalImageUrl: ?string,
     *     status: string,
     *     episode?: list<array<string, mixed>>,
     * }
     */
    public static function toArray(Podcast $podcast, bool $includeEpisodes = false): array
    {
        $payload = [
            'id' => $podcast->id,
            'url' => $podcast->url,
            'title' => $podcast->title,
            'description' => $podcast->description ?: null,
            'coverArt' => $podcast->id,
            'originalImageUrl' => $podcast->image ?: null,
            'status' => 'completed',
        ];

        if ($includeEpisodes) {
            $payload['episode'] = $podcast->episodes->map(PodcastEpisodeResource::toArray(...))->all();
        }

        return $payload;
    }
}
