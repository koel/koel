<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\LastfmService;
use App\Services\MediaInformationService;
use App\Services\MediaMetadataService;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;

class MediaInformationServiceTest extends TestCase
{
    private LastfmService|MockInterface|LegacyMockInterface $lastFmService;
    private MediaMetadataService|LegacyMockInterface|MockInterface $mediaMetadataService;
    private MediaInformationService $mediaInformationService;

    public function setUp(): void
    {
        parent::setUp();

        $this->lastFmService = Mockery::mock(LastfmService::class);
        $this->mediaMetadataService = Mockery::mock(MediaMetadataService::class);

        $this->mediaInformationService = new MediaInformationService($this->lastFmService, $this->mediaMetadataService);
    }

    public function testGetAlbumInformation(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $info = AlbumInformation::make();

        $this->lastFmService
            ->shouldReceive('getAlbumInformation')
            ->once()
            ->with($album)
            ->andReturn($info);

        self::assertSame($info, $this->mediaInformationService->getAlbumInformation($album));
    }

    public function testGetAlbumInformationTriesDownloadingCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['cover' => '']);
        $info = AlbumInformation::make();

        $this->lastFmService
            ->shouldReceive('getAlbumInformation')
            ->once()
            ->with($album)
            ->andReturn($info);

        $this->mediaMetadataService
            ->shouldReceive('tryDownloadAlbumCover')
            ->with($album);

        self::assertSame($info, $this->mediaInformationService->getAlbumInformation($album));
    }

    public function testGetArtistInformation(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $info = ArtistInformation::make();

        $this->lastFmService
            ->shouldReceive('getArtistInformation')
            ->once()
            ->with($artist)
            ->andReturn($info);

        self::assertSame($info, $this->mediaInformationService->getArtistInformation($artist));
    }

    public function testGetArtistInformationTriesDownloadingImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['image' => '']);
        $info = ArtistInformation::make();

        $this->lastFmService
            ->shouldReceive('getArtistInformation')
            ->once()
            ->with($artist)
            ->andReturn($info);

        $this->mediaMetadataService
            ->shouldReceive('tryDownloadArtistImage')
            ->with($artist);

        self::assertSame($info, $this->mediaInformationService->getArtistInformation($artist));
    }
}
