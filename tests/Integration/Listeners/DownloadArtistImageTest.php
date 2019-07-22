<?php

namespace Tests\Integration\Listeners;

use App\Events\ArtistInformationFetched;
use App\Models\Artist;
use App\Services\MediaMetadataService;
use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;
use Tests\TestCase;

class DownloadArtistImageTest extends TestCase
{
    /** @var MediaMetadataService|MockInterface */
    private $mediaMetaDataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetaDataService = $this->mockIocDependency(MediaMetadataService::class);
        PHPMockery::mock('App\Listeners', 'ini_get')->andReturn(true);
    }

    public function testHandle(): void
    {
        $artist = factory(Artist::class)->make(['image' => null]);
        $event = new ArtistInformationFetched($artist, ['image' => 'https://foo.bar/baz.jpg']);

        $this->mediaMetaDataService
            ->shouldReceive('downloadArtistImage')
            ->once()
            ->with($artist, 'https://foo.bar/baz.jpg');

        event($event);
    }
}
