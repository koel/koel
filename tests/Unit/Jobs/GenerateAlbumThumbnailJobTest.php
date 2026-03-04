<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Services\AlbumService;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateAlbumThumbnailJobTest extends TestCase
{
    #[Test]
    public function handle(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        /** @var AlbumService|MockInterface $albumService */
        $albumService = $this->mock(AlbumService::class);
        $albumService->expects('generateAlbumThumbnail')->with($album)->once();

        (new GenerateAlbumThumbnailJob($album))->handle($albumService);
    }
}
