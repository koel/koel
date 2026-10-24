<?php

namespace Tests\Unit\Values;

use App\Enums\NativelyPlayableLosslessFormat;
use App\Values\RequestedStreamingConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequestedStreamingConfigTest extends TestCase
{
    #[Test]
    public function clientPlaysMimeTypeOfAListedFormat(): void
    {
        $config = RequestedStreamingConfig::make(clientLosslessFormats: [
            NativelyPlayableLosslessFormat::WAV,
            NativelyPlayableLosslessFormat::FLAC,
        ]);

        self::assertTrue($config->clientPlaysLosslessMimeType('audio/x-flac'));
    }

    #[Test]
    public function clientDoesNotPlayMimeTypeOfAnUnlistedFormat(): void
    {
        $config = RequestedStreamingConfig::make(clientLosslessFormats: [NativelyPlayableLosslessFormat::FLAC]);

        self::assertFalse($config->clientPlaysLosslessMimeType('audio/x-m4a'));
    }
}
