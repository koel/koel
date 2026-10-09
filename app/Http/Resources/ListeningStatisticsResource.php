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
        'previous_plays',
        'discoveries',
        'streak' => ['current_days', 'longest_days'],
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
            'previous_plays' => $this->statistics->previousPlays,
            'discoveries' => $this->statistics->discoveries
                ? [
                    'song_count' => $this->statistics->discoveries->songCount,
                    'artist_count' => $this->statistics->discoveries->artistCount,
                ] : null,
            'streak' => [
                'current_days' => $this->statistics->streak->currentDays,
                'longest_days' => $this->statistics->streak->longestDays,
            ],
        ];
    }
}
