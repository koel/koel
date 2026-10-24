<?php

namespace App\Http\Resources;

use App\Values\Statistics\ListeningStats;
use App\Values\Statistics\ListeningSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeningStatsResource extends JsonResource
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
        private readonly ListeningStats $stats,
    ) {
        parent::__construct($stats);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        return [
            'summary' => self::summaryToArray($this->stats->summary),
            'top_songs' => array_map(static fn (array $entry): array => [
                'plays' => $entry['plays'],
                'song' => SongResource::make($entry['song']),
            ], $this->stats->topSongs),
            'top_artists' => array_map(static fn (array $entry): array => [
                'listening_time' => $entry['listening_time'],
                'artist' => ArtistResource::make($entry['artist']),
                'album_cover' => $entry['album_cover'],
            ], $this->stats->topArtists),
            'top_albums' => array_map(static fn (array $entry): array => [
                'listening_time' => $entry['listening_time'],
                'album' => AlbumResource::make($entry['album']),
            ], $this->stats->topAlbums),
            'top_genres' => $this->stats->topGenres,
            'hourly_listening' => collect($this->stats->hourlyListening)
                ->map(static fn (array $listening, string $hour): array => ['hour' => $hour, ...$listening])
                ->values()
                ->all(),
            'previous_summary' => $this->stats->previousSummary
                ? self::summaryToArray($this->stats->previousSummary)
                : null,
            'discoveries' => $this->stats->discoveries
                ? [
                    'song_count' => $this->stats->discoveries->songCount,
                    'artist_count' => $this->stats->discoveries->artistCount,
                ] : null,
            'streak' => [
                'current_days' => $this->stats->streak->currentDays,
                'longest_days' => $this->stats->streak->longestDays,
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
