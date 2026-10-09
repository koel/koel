<?php

namespace App\Values\Statistics;

final readonly class ListeningSummary
{
    private function __construct(
        public int $plays,
        public float $listeningTime,
        public int $songCount,
        public int $artistCount,
    ) {}

    public static function make(int $plays, float $listeningTime, int $songCount, int $artistCount): self
    {
        return new self(plays: $plays, listeningTime: $listeningTime, songCount: $songCount, artistCount: $artistCount);
    }
}
