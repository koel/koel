<?php

namespace App\Values\Statistics;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;

final readonly class ListeningStatistics
{
    /**
     * @param list<array{song: Song, plays: int}> $topSongs
     * @param list<array{artist: Artist, plays: int}> $topArtists
     * @param list<array{album: Album, plays: int}> $topAlbums
     * @param list<array{id: string, name: string, plays: int}> $topGenres
     * @param array<string, int> $hourlyPlays play counts keyed by UTC hour (ISO 8601)
     */
    private function __construct(
        public ListeningSummary $summary,
        public array $topSongs,
        public array $topArtists,
        public array $topAlbums,
        public array $topGenres,
        public array $hourlyPlays,
    ) {}

    /**
     * @param list<array{song: Song, plays: int}> $topSongs
     * @param list<array{artist: Artist, plays: int}> $topArtists
     * @param list<array{album: Album, plays: int}> $topAlbums
     * @param list<array{id: string, name: string, plays: int}> $topGenres
     * @param array<string, int> $hourlyPlays
     */
    public static function make(
        ListeningSummary $summary,
        array $topSongs,
        array $topArtists,
        array $topAlbums,
        array $topGenres,
        array $hourlyPlays,
    ): self {
        return new self(
            summary: $summary,
            topSongs: $topSongs,
            topArtists: $topArtists,
            topAlbums: $topAlbums,
            topGenres: $topGenres,
            hourlyPlays: $hourlyPlays,
        );
    }
}
