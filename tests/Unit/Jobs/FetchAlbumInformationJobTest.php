<?php

namespace Tests\Unit\Jobs;

use App\Exceptions\MusicBrainzBusyException;
use App\Jobs\FetchAlbumInformationJob;
use App\Models\Album;
use App\Services\Integrations\EncyclopediaService;
use App\Services\Integrations\MusicBrainzRateLimiter;
use Closure;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchAlbumInformationJobTest extends TestCase
{
    #[Test]
    public function waitForAMusicBrainzSlotWhileFetching(): void
    {
        $album = Album::factory()->createOne();

        $encyclopediaService = Mockery::mock(EncyclopediaService::class);
        $encyclopediaService->expects('getAlbumInformationOrThrowIfMusicBrainzIsBusy')->with($album);

        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter
            ->expects('waitForRequestSlotsUpTo')
            ->with(30.0, Mockery::type(Closure::class))
            ->andReturnUsing(static fn (float $seconds, Closure $callback): mixed => $callback());

        (new FetchAlbumInformationJob($album))->handle($encyclopediaService, $rateLimiter);
    }

    #[Test]
    public function runOnceAtATimePerAlbum(): void
    {
        $album = Album::factory()->createOne();

        self::assertSame((string) $album->id, (new FetchAlbumInformationJob($album))->uniqueId());
    }

    #[Test]
    public function tryAgainLaterWhenNoSlotFreesUp(): void
    {
        $album = Album::factory()->createOne();

        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter->expects('waitForRequestSlotsUpTo')->andThrow(MusicBrainzBusyException::create());

        $job = (new FetchAlbumInformationJob($album))->withFakeQueueInteractions();
        $job->handle(Mockery::mock(EncyclopediaService::class), $rateLimiter);

        $job->assertReleased(delay: 60);
    }
}
