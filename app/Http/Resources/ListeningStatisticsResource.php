<?php

namespace App\Http\Resources;

use App\Values\Statistics\ListeningStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningStatisticsResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'summary' => ['plays', 'listening_time', 'song_count', 'artist_count'],
        'top_songs',
        'top_artists',
        'top_albums',
        'top_genres',
        'hourly_plays',
        'first_played_at',
    ];

    public function __construct(
        private readonly ListeningStatistics $statistics,
    ) {
        parent::__construct($statistics);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $summary = $this->statistics->summary;

        return [
            'summary' => [
                'plays' => $summary->plays,
                'listening_time' => $summary->listeningTime,
                'song_count' => $summary->songCount,
                'artist_count' => $summary->artistCount,
            ],
            'top_songs' => array_map(static fn (array $entry): array => [
                'plays' => $entry['plays'],
                'song' => SongResource::make($entry['song']),
            ], $this->statistics->topSongs),
            'top_artists' => array_map(static fn (array $entry): array => [
                'plays' => $entry['plays'],
                'artist' => ArtistResource::make($entry['artist']),
            ], $this->statistics->topArtists),
            'top_albums' => array_map(static fn (array $entry): array => [
                'plays' => $entry['plays'],
                'album' => AlbumResource::make($entry['album']),
            ], $this->statistics->topAlbums),
            'top_genres' => $this->statistics->topGenres,
            'hourly_plays' => collect($this->statistics->hourlyPlays)
                ->map(static fn (int $plays, string $hour): array => ['hour' => $hour, 'plays' => $plays])
                ->values()
                ->all(),
            'first_played_at' => $this->statistics->firstPlayedAt?->toIso8601ZuluString(),
        ];
    }
}
