<?php

namespace App\Http\Resources;

use App\Values\Statistics\ListeningStatistics;
use App\Values\Statistics\ListeningSummary;
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
        'hourly_listening',
        'previous_summary',
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
        return [
            'summary' => self::summaryToArray($this->statistics->summary),
            'top_songs' => array_map(static fn (array $entry): array => [
                'plays' => $entry['plays'],
                'song' => SongResource::make($entry['song']),
            ], $this->statistics->topSongs),
            'top_artists' => array_map(static fn (array $entry): array => [
                'listening_time' => $entry['listening_time'],
                'artist' => ArtistResource::make($entry['artist']),
            ], $this->statistics->topArtists),
            'top_albums' => array_map(static fn (array $entry): array => [
                'listening_time' => $entry['listening_time'],
                'album' => AlbumResource::make($entry['album']),
            ], $this->statistics->topAlbums),
            'top_genres' => $this->statistics->topGenres,
            'hourly_listening' => collect($this->statistics->hourlyListening)
                ->map(static fn (array $listening, string $hour): array => ['hour' => $hour, ...$listening])
                ->values()
                ->all(),
            'previous_summary' => $this->statistics->previousSummary
                ? self::summaryToArray($this->statistics->previousSummary)
                : null,
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

    /** @return array{plays: int, listening_time: float, song_count: int, artist_count: int} */
    private static function summaryToArray(ListeningSummary $summary): array
    {
        return [
            'plays' => $summary->plays,
            'listening_time' => $summary->listeningTime,
            'song_count' => $summary->songCount,
            'artist_count' => $summary->artistCount,
        ];
    }
}
