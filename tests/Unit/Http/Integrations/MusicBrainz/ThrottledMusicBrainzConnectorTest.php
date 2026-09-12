<?php

namespace Tests\Unit\Http\Integrations\MusicBrainz;

use App\Http\Integrations\MusicBrainz\ThrottledMusicBrainzConnector;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\PendingRequest;
use Tests\TestCase;

class ThrottledMusicBrainzConnectorTest extends TestCase
{
    private const float SHORT_INTERVAL = 0.05;
    private const float LONG_INTERVAL = 30.0;

    #[Test]
    public function letTheFirstRequestThrough(): void
    {
        $connector = new ThrottledMusicBrainzConnector(self::LONG_INTERVAL);

        $elapsed = self::timeBoot($connector, 1);

        self::assertLessThan(self::LONG_INTERVAL / 2, $elapsed);
    }

    #[Test]
    public function holdEachFollowingRequestBackByTheInterval(): void
    {
        $connector = new ThrottledMusicBrainzConnector(self::SHORT_INTERVAL);

        $elapsed = self::timeBoot($connector, 3);

        self::assertGreaterThanOrEqual(self::SHORT_INTERVAL * 2, $elapsed);
    }

    #[Test]
    public function keepToOneRequestPerSecondByDefault(): void
    {
        $elapsed = self::timeBoot(new ThrottledMusicBrainzConnector(), 2);

        self::assertGreaterThanOrEqual(1.0, $elapsed);
    }

    private static function timeBoot(ThrottledMusicBrainzConnector $connector, int $times): float
    {
        $pendingRequest = Mockery::mock(PendingRequest::class);
        $startedAt = microtime(true);

        for ($i = 0; $i < $times; $i++) {
            $connector->boot($pendingRequest);
        }

        return microtime(true) - $startedAt;
    }
}
