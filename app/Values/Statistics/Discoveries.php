<?php

namespace App\Values\Statistics;

final readonly class Discoveries
{
    private function __construct(
        public int $songCount,
        public int $artistCount,
    ) {}

    public static function make(int $songCount, int $artistCount): self
    {
        return new self(songCount: $songCount, artistCount: $artistCount);
    }
}
