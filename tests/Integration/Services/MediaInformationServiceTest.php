<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\LastfmService;
use App\Services\MediaInformationService;
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

    /** @test */
    public function it_gets_album_information()
    {
        /** @var Album $album */
        $album = factory(Album::class)->create();

        $this->lastFmService
            ->shouldReceive('getAlbumInfo')
            ->once()
            ->with($album->name, $album->artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $this->mediaInformationService->getAlbumInformation($album);

        self::assertEquals(['foo' => 'bar'], $info);
    }

    /** @test */
    public function it_gets_artist_information()
    {
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();

        $this->lastFmService
            ->shouldReceive('getArtistInfo')
            ->once()
            ->with($artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $this->mediaInformationService->getArtistInformation($artist);

        self::assertEquals(['foo' => 'bar'], $info);
    }
}
