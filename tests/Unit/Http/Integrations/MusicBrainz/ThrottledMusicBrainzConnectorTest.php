<?php

namespace Tests\Unit\Http\Integrations\MusicBrainz;

use App\Http\Integrations\MusicBrainz\ThrottledMusicBrainzConnector;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\PendingRequest;
use Tests\TestCase;

class ThrottledMusicBrainzConnectorTest extends TestCase
{
    private const float INTERVAL = 0.05;

    #[Test]
    public function letTheFirstRequestThrough(): void
    {
        $connector = new ThrottledMusicBrainzConnector(self::INTERVAL);

        $elapsed = self::timeBoot($connector, 1);

        self::assertLessThan(self::INTERVAL, $elapsed);
    }

    #[Test]
    public function holdEachFollowingRequestBackByTheInterval(): void
    {
        $connector = new ThrottledMusicBrainzConnector(self::INTERVAL);

        $elapsed = self::timeBoot($connector, 3);

        self::assertGreaterThanOrEqual(self::INTERVAL * 2, $elapsed);
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
