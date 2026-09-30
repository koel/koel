<?php

namespace Tests\Unit\Services\Integrations;

use App\Exceptions\MusicBrainzBusyException;
use App\Services\Integrations\MusicBrainzRateLimiter;
use Carbon\CarbonInterval;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MusicBrainzRateLimiterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
    }

    #[Test]
    public function giveTheFirstRequestASlotRightAway(): void
    {
        (new MusicBrainzRateLimiter())->takeSlot();

        Sleep::assertNeverSlept();
    }

    #[Test]
    public function makeTheNextRequestWaitASecondWhenAllowedToWait(): void
    {
        $limiter = new MusicBrainzRateLimiter();
        $limiter->takeSlot();

        $limiter->waitUpTo(5, $limiter->takeSlot(...));

        Sleep::assertSlept(static fn (CarbonInterval $waited): bool => $waited->totalSeconds >= 1.0);
    }

    #[Test]
    public function refuseTheNextRequestWhenItCannotWait(): void
    {
        $limiter = new MusicBrainzRateLimiter('redis');
        $limiter->takeSlot();

        $this->expectException(MusicBrainzBusyException::class);

        $limiter->takeSlot();
    }

    #[Test]
    public function shareTheSlotBetweenLimiterInstances(): void
    {
        (new MusicBrainzRateLimiter('redis'))->takeSlot();

        $this->expectException(MusicBrainzBusyException::class);

        (new MusicBrainzRateLimiter('redis'))->takeSlot();
    }

    #[Test]
    public function waitAFewSecondsInsideTheRequestWithoutAQueue(): void
    {
        $limiter = new MusicBrainzRateLimiter('sync');
        $limiter->takeSlot();

        $limiter->takeSlot();

        Sleep::assertSleptTimes(1);
    }

    #[Test]
    public function holdEveryRequestBackAfterMusicBrainzRefusesOne(): void
    {
        $limiter = new MusicBrainzRateLimiter('sync');
        $limiter->backOff();

        $this->expectException(MusicBrainzBusyException::class);

        $limiter->takeSlot();
    }

    #[Test]
    public function fillInTheBackgroundOnlyWithAQueue(): void
    {
        self::assertTrue((new MusicBrainzRateLimiter('redis'))->fillsInBackground());
        self::assertFalse((new MusicBrainzRateLimiter('sync'))->fillsInBackground());
    }
}
