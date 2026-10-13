<?php

namespace Tests\Unit\Services\Integrations;

use App\Exceptions\MusicBrainzBusyException;
use App\Services\Integrations\MusicBrainzService;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class MusicBrainzServiceTest extends TestCase
{
    #[Test]
    public function rethrowWhenMusicBrainzIsBusy(): void
    {
        $this->expectException(MusicBrainzBusyException::class);

        MusicBrainzService::rescueUnlessMusicBrainzIsBusy(
            static fn () => throw MusicBrainzBusyException::create(),
            static fn (): string => 'fallback',
        );
    }

    #[Test]
    public function fallBackOnOtherFailures(): void
    {
        $result = MusicBrainzService::rescueUnlessMusicBrainzIsBusy(
            static fn () => throw new RuntimeException('Boom'),
            static fn (): string => 'fallback',
        );

        self::assertSame('fallback', $result);
    }

    #[Test]
    public function returnNullOnOtherFailuresWithoutAFallback(): void
    {
        self::assertNull(MusicBrainzService::rescueUnlessMusicBrainzIsBusy(self::failToLookUp(...)));
    }

    private static function failToLookUp(): string
    {
        throw new RuntimeException('Boom');
    }
}
