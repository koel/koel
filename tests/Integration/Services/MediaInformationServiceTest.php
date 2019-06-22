<?php

namespace Tests\Integration\Services;

use App\Events\AlbumInformationFetched;
use App\Events\ArtistInformationFetched;
use App\Models\Album;
use App\Models\Artist;
use App\Services\LastfmService;
use App\Services\MediaInformationService;
use Exception;
use Mockery as m;
use Mockery\MockInterface;
use Tests\TestCase;

class MediaInformationServiceTest extends TestCase
{
    /**
     * @var LastfmService|MockInterface
     */
    private $lastFmService;

    /**
     * @var MediaInformationService
     */
    private $mediaInformationService;

    public function setUp()
    {
        parent::setUp();

        $this->lastFmService = m::mock(LastfmService::class);
        $this->mediaInformationService = new MediaInformationService($this->lastFmService);
    }

    /**
     * @throws Exception
     */
    public function testGetAlbumInformation()
    {
        $this->expectsEvents(AlbumInformationFetched::class);

        /** @var Album $album */
        $album = factory(Album::class)->create();

        $this->lastFmService
            ->shouldReceive('getAlbumInformation')
            ->once()
            ->with($album->name, $album->artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $this->mediaInformationService->getAlbumInformation($album);

        self::assertEquals([
            'foo' => 'bar',
            'cover' => $album->cover,
        ], $info);
    }

    /**
     * @throws Exception
     */
    public function testGetArtistInformation()
    {
        $this->expectsEvents(ArtistInformationFetched::class);

        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();

        $this->lastFmService
            ->shouldReceive('getArtistInformation')
            ->once()
            ->with($artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $this->mediaInformationService->getArtistInformation($artist);

        self::assertEquals([
            'foo' => 'bar',
            'image' => $artist->image,
        ], $info);
    }
}
