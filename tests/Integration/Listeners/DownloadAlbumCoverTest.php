<?php

namespace Tests\Integration\Listeners;

use App\Events\AlbumInformationFetched;
use App\Models\Album;
use App\Services\MediaMetadataService;
use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;
use Tests\TestCase;

class DownloadAlbumCoverTest extends TestCase
{
    /** @var MediaMetadataService|MockInterface */
    private $mediaMetaDataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetaDataService = self::mock(MediaMetadataService::class);
        PHPMockery::mock('App\Listeners', 'ini_get')->andReturn(true);
    }

    public function testHandle(): void
    {
        /** @var Album $album */
        $album = Album::factory()->make(['cover' => null]);
        $event = new AlbumInformationFetched($album, ['image' => 'https://foo.bar/baz.jpg']);

        $this->mediaMetaDataService
            ->shouldReceive('downloadAlbumCover')
            ->once()
            ->with($album, 'https://foo.bar/baz.jpg');

        event($event);
    }
}
