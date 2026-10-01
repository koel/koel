<?php

namespace Tests\Unit\Jobs;

use App\Exceptions\MusicBrainzBusyException;
use App\Jobs\FetchArtistInformationJob;
use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;
use App\Services\Integrations\MusicBrainzRateLimiter;
use Closure;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchArtistInformationJobTest extends TestCase
{
    #[Test]
    public function waitForAMusicBrainzSlotWhileFetching(): void
    {
        $artist = Artist::factory()->createOne();

        $encyclopediaService = Mockery::mock(EncyclopediaService::class);
        $encyclopediaService->expects('getArtistInformationOrThrowIfMusicBrainzIsBusy')->with($artist);

        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter
            ->expects('waitForRequestSlotsUpTo')
            ->with(30.0, Mockery::type(Closure::class))
            ->andReturnUsing(static fn (float $seconds, Closure $callback): mixed => $callback());

        (new FetchArtistInformationJob($artist))->handle($encyclopediaService, $rateLimiter);
    }

    #[Test]
    public function runOnceAtATimePerArtist(): void
    {
        $artist = Artist::factory()->createOne();

        self::assertSame((string) $artist->id, (new FetchArtistInformationJob($artist))->uniqueId());
    }

    #[Test]
    public function tryAgainLaterWhenNoSlotFreesUp(): void
    {
        $artist = Artist::factory()->createOne();

        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter->expects('waitForRequestSlotsUpTo')->andThrow(MusicBrainzBusyException::create());

        $job = (new FetchArtistInformationJob($artist))->withFakeQueueInteractions();
        $job->handle(Mockery::mock(EncyclopediaService::class), $rateLimiter);

        $job->assertReleased(delay: 60);
    }
}
