<?php

namespace Tests\Unit\Values;

use App\Enums\LosslessCodec;
use App\Values\RequestedStreamingConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequestedStreamingConfigTest extends TestCase
{
    #[Test]
    public function clientPlaysMimeTypeOfAListedCodec(): void
    {
        $config = RequestedStreamingConfig::make(clientLosslessCodecs: [LosslessCodec::WAV, LosslessCodec::FLAC]);

        self::assertTrue($config->clientPlaysMimeTypeLosslessly('audio/x-flac'));
    }

    #[Test]
    public function clientDoesNotPlayMimeTypeOfAnUnlistedCodec(): void
    {
        $config = RequestedStreamingConfig::make(clientLosslessCodecs: [LosslessCodec::FLAC]);

        self::assertFalse($config->clientPlaysMimeTypeLosslessly('audio/x-m4a'));
    }
}
