<?php

namespace App\Values\Statistics;

final readonly class ListeningStreak
{
    private function __construct(
        public int $currentDays,
        public int $longestDays,
    ) {}

    public static function make(int $currentDays, int $longestDays): self
    {
        return new self(currentDays: $currentDays, longestDays: $longestDays);
    }
}
