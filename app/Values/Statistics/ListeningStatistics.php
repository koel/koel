<?php

namespace App\Values\Statistics;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;

final readonly class ListeningStatistics
{
    /**
     * @param list<array{song: Song, plays: int}> $topSongs
     * @param list<array{artist: Artist, listening_time: float, album_cover: ?string}> $topArtists
     * @param list<array{album: Album, listening_time: float}> $topAlbums
     * @param list<array{id: string, name: string, listening_time: float}> $topGenres
     * @param array<string, array{plays: int, listening_time: float}> $hourlyListening keyed by UTC hour (ISO 8601)
     */
    private function __construct(
        public ListeningSummary $summary,
        public array $topSongs,
        public array $topArtists,
        public array $topAlbums,
        public array $topGenres,
        public array $hourlyListening,
        public ?ListeningSummary $previousSummary,
        public ?Discoveries $discoveries,
        public ListeningStreak $streak,
    ) {}

    /**
     * @param list<array{song: Song, plays: int}> $topSongs
     * @param list<array{artist: Artist, listening_time: float, album_cover: ?string}> $topArtists
     * @param list<array{album: Album, listening_time: float}> $topAlbums
     * @param list<array{id: string, name: string, listening_time: float}> $topGenres
     * @param array<string, array{plays: int, listening_time: float}> $hourlyListening
     */
    public static function make(
        ListeningSummary $summary,
        array $topSongs,
        array $topArtists,
        array $topAlbums,
        array $topGenres,
        array $hourlyListening,
        ?ListeningSummary $previousSummary,
        ?Discoveries $discoveries,
        ListeningStreak $streak,
    ): self {
        return new self(
            summary: $summary,
            topSongs: $topSongs,
            topArtists: $topArtists,
            topAlbums: $topAlbums,
            topGenres: $topGenres,
            hourlyListening: $hourlyListening,
            previousSummary: $previousSummary,
            discoveries: $discoveries,
            streak: $streak,
        );
    }
}
