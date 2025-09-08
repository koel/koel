<?php

namespace App\Values;

final readonly class RequestedStreamingConfig
{
    private function __construct(
        public bool $transcode,
        public ?int $bitRate,
        public float $startTime
    ) {
    }

    public static function make(bool $transcode = false, ?int $bitRate = 128, float $startTime = 0.0): self
    {
        return new self($transcode, $bitRate, $startTime);
    }
}
