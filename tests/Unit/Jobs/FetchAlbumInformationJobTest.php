<?php

namespace Tests\Unit\Jobs;

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
        $encyclopediaService->expects('getAlbumInformation')->with($album);

        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter
            ->expects('waitUpTo')
            ->with(60.0, Mockery::type(Closure::class))
            ->andReturnUsing(static fn (float $seconds, Closure $callback): mixed => $callback());

        (new FetchAlbumInformationJob($album))->handle($encyclopediaService, $rateLimiter);
    }

    #[Test]
    public function runOnceAtATimePerAlbum(): void
    {
        $album = Album::factory()->createOne();

        self::assertSame((string) $album->id, (new FetchAlbumInformationJob($album))->uniqueId());
    }
}
