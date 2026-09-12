<?php

namespace Tests\Unit\Http\Integrations\MusicBrainz;

use App\Http\Integrations\MusicBrainz\ThrottledMusicBrainzConnector;
use Carbon\CarbonInterval;
use Illuminate\Support\Sleep;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\PendingRequest;
use Tests\TestCase;

class ThrottledMusicBrainzConnectorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
    }

    #[Test]
    public function letTheFirstRequestThrough(): void
    {
        self::boot(new ThrottledMusicBrainzConnector(), times: 1);

        Sleep::assertNeverSlept();
    }

    #[Test]
    public function holdEachFollowingRequestBack(): void
    {
        self::boot(new ThrottledMusicBrainzConnector(), times: 3);

        Sleep::assertSleptTimes(2);
    }

    #[Test]
    public function keepToOneRequestPerSecondByDefault(): void
    {
        self::boot(new ThrottledMusicBrainzConnector(), times: 2);

        Sleep::assertSlept(static fn (CarbonInterval $waited): bool => $waited->totalSeconds > 0.99);
    }

    #[Test]
    public function waitOnlyAsLongAsAskedFor(): void
    {
        self::boot(new ThrottledMusicBrainzConnector(0.25), times: 2);

        Sleep::assertSlept(static fn (CarbonInterval $waited): bool => $waited->totalSeconds < 0.26);
    }

    private static function boot(ThrottledMusicBrainzConnector $connector, int $times): void
    {
        $pendingRequest = Mockery::mock(PendingRequest::class);

        for ($i = 0; $i < $times; $i++) {
            $connector->boot($pendingRequest);
        }
    }
}
