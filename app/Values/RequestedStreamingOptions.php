<?php

namespace App\Values;

class RequestedStreamingConfig
{
    private function __construct(
        public readonly bool $transcode,
        public readonly int $bitRate,
        public readonly float $startTime
    ) {
    }

    public static function make(bool $transcode = false, int $bitRate = 128, float $startTime = 0.0): self
    {
        return new self($transcode, $bitRate, $startTime);
    }
}
