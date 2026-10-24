<?php

namespace App\Values;

use App\Enums\NativelyPlayableLosslessFormat;

final readonly class RequestedStreamingConfig
{
    /** @param list<NativelyPlayableLosslessFormat> $clientLosslessFormats */
    private function __construct(
        public bool $transcode,
        public ?int $bitRate,
        public float $startTime,
        public array $clientLosslessFormats,
        public bool $streamOriginal,
    ) {}

    /** @param list<NativelyPlayableLosslessFormat> $clientLosslessFormats */
    public static function make(
        bool $transcode = false,
        ?int $bitRate = 128,
        float $startTime = 0.0,
        array $clientLosslessFormats = [],
        bool $streamOriginal = false,
    ): self {
        return new self($transcode, $bitRate, $startTime, $clientLosslessFormats, $streamOriginal);
    }

    public function clientPlaysLosslessMimeType(string $mimeType): bool
    {
        return collect($this->clientLosslessFormats)
            ->contains(static fn (NativelyPlayableLosslessFormat $format): bool => in_array(
                $mimeType,
                $format->mimeTypes(),
                true,
            ));
    }
}
