<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateAlbumSmallCoverJob;
use App\Models\Album;
use App\Services\AlbumService;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateAlbumSmallCoverJobTest extends TestCase
{
    #[Test]
    public function handle(): void
    {
        $album = Album::factory()->createOne();

        /** @var AlbumService|MockInterface $albumService */
        $albumService = $this->mock(AlbumService::class);
        $albumService->expects('generateAlbumSmallCover')->with($album)->once();

        (new GenerateAlbumSmallCoverJob($album))->handle($albumService);
    }
}
