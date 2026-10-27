<?php

namespace App\Values\Transcoding;

final readonly class PreviewSection
{
    private const int MAX_DURATION_SECONDS = 30;

    private function __construct(
        public float $start,
        public float $duration,
    ) {}

    public static function make(float $songLength): self
    {
        $latestStart = max($songLength - self::MAX_DURATION_SECONDS, 0);

        return new self(
            start: min($songLength / 3, $latestStart),
            duration: min($songLength, self::MAX_DURATION_SECONDS),
        );
    }
}
