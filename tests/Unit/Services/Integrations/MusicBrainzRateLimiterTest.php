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
        (new MusicBrainzRateLimiter())->takeRequestSlot();

        Sleep::assertNeverSlept();
    }

    #[Test]
    public function makeTheNextRequestWaitASecondWhenAllowedToWait(): void
    {
        $limiter = new MusicBrainzRateLimiter();
        $limiter->takeRequestSlot();

        $limiter->waitForRequestSlotsUpTo(5, $limiter->takeRequestSlot(...));

        Sleep::assertSlept(static fn (CarbonInterval $waited): bool => $waited->totalSeconds >= 1.0);
    }

    #[Test]
    public function refuseTheNextRequestWhenItCannotWait(): void
    {
        $limiter = new MusicBrainzRateLimiter('redis');
        $limiter->takeRequestSlot();

        $this->expectException(MusicBrainzBusyException::class);

        $limiter->takeRequestSlot();
    }

    #[Test]
    public function shareTheSlotBetweenLimiterInstances(): void
    {
        (new MusicBrainzRateLimiter('redis'))->takeRequestSlot();

        $this->expectException(MusicBrainzBusyException::class);

        (new MusicBrainzRateLimiter('redis'))->takeRequestSlot();
    }

    #[Test]
    public function waitAFewSecondsInsideTheRequestWithoutAQueue(): void
    {
        $limiter = new MusicBrainzRateLimiter('sync');
        $limiter->takeRequestSlot();

        $limiter->takeRequestSlot();

        Sleep::assertSleptTimes(1);
    }

    #[Test]
    public function holdEveryRequestBackAfterMusicBrainzRefusesOne(): void
    {
        $limiter = new MusicBrainzRateLimiter('sync');
        $limiter->backOff();

        $this->expectException(MusicBrainzBusyException::class);

        $limiter->takeRequestSlot();
    }

    #[Test]
    public function queueLookupsOnlyWithAQueue(): void
    {
        self::assertTrue((new MusicBrainzRateLimiter('redis'))->canQueueLookups());
        self::assertFalse((new MusicBrainzRateLimiter('sync'))->canQueueLookups());
    }

    #[Test]
    public function neverShortenABackOffAlreadyUnderWay(): void
    {
        $limiter = new MusicBrainzRateLimiter('redis');
        $limiter->backOff();

        $this->travel(-20)->seconds();
        $limiter->backOff();
        $this->travel(35)->seconds();

        $this->expectException(MusicBrainzBusyException::class);

        $limiter->takeRequestSlot();
    }

    #[Test]
    public function shareOneWaitingBudgetAcrossEveryRequestInside(): void
    {
        $limiter = new MusicBrainzRateLimiter('redis');

        $this->expectException(MusicBrainzBusyException::class);

        $limiter->waitForRequestSlotsUpTo(1.5, static function () use ($limiter): void {
            $limiter->takeRequestSlot();
            $limiter->takeRequestSlot();
            $limiter->takeRequestSlot();
        });
    }
}
