<?php

namespace App\Values;

use App\Enums\LosslessCodec;

final readonly class RequestedStreamingConfig
{
    /** @param list<LosslessCodec> $clientLosslessCodecs */
    private function __construct(
        public bool $transcode,
        public ?int $bitRate,
        public float $startTime,
        public array $clientLosslessCodecs,
    ) {}

    /** @param list<LosslessCodec> $clientLosslessCodecs */
    public static function make(
        bool $transcode = false,
        ?int $bitRate = 128,
        float $startTime = 0.0,
        array $clientLosslessCodecs = [],
    ): self {
        return new self($transcode, $bitRate, $startTime, $clientLosslessCodecs);
    }

    public function clientPlaysMimeTypeLosslessly(string $mimeType): bool
    {
        return collect($this->clientLosslessCodecs)
            ->contains(static fn (LosslessCodec $codec): bool => in_array($mimeType, $codec->mimeTypes(), true));
    }
}
