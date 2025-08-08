<?php

namespace App\Values;

use Illuminate\Support\Collection;

final class ExcerptSearchResult
{
    private function __construct(
        public Collection $songs,
        public Collection $artists,
        public Collection $albums,
        public Collection $podcasts,
        public Collection $radioStations,
    ) {
    }

    public static function make(
        Collection $songs,
        Collection $artists,
        Collection $albums,
        Collection $podcasts,
        Collection $radioStations,
    ): self {
        return new self($songs, $artists, $albums, $podcasts, $radioStations);
    }
}
