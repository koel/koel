<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Contracts\MusicEncyclopedia;
use App\Services\LastfmService;
use App\Services\MediaInformationService;
use App\Services\MediaMetadataService;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaInformationServiceTest extends TestCase
{
    private MusicEncyclopedia|MockInterface|LegacyMockInterface $encyclopedia;
    private MediaMetadataService|LegacyMockInterface|MockInterface $mediaMetadataService;
    private MediaInformationService $mediaInformationService;

    public function setUp(): void
    {
        parent::setUp();

        $this->encyclopedia = Mockery::mock(LastfmService::class);
        $this->mediaMetadataService = Mockery::mock(MediaMetadataService::class);

        $this->mediaInformationService = new MediaInformationService($this->encyclopedia, $this->mediaMetadataService);
    }

    #[Test]
    public function getAlbumInformation(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $info = AlbumInformation::make();

        $this->encyclopedia
            ->shouldReceive('getAlbumInformation')
            ->once()
            ->with($album)
            ->andReturn($info);

        $this->mediaMetadataService
            ->shouldNotReceive('tryDownloadAlbumCover');

        self::assertSame($info, $this->mediaInformationService->getAlbumInformation($album));
        self::assertNotNull(cache()->get('album.info.' . $album->id));
    }

    #[Test]
    public function getAlbumInformationTriesDownloadingCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['cover' => '']);
        $info = AlbumInformation::make();

        self::assertFalse($album->has_cover);

        $this->encyclopedia
            ->shouldReceive('getAlbumInformation')
            ->once()
            ->with($album)
            ->andReturn($info);

        $this->mediaMetadataService
            ->shouldReceive('tryDownloadAlbumCover')
            ->with($album);

        self::assertSame($info, $this->mediaInformationService->getAlbumInformation($album));
    }

    #[Test]
    public function getArtistInformation(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $info = ArtistInformation::make();

        self::assertTrue($artist->has_image);

        $this->encyclopedia
            ->shouldReceive('getArtistInformation')
            ->once()
            ->with($artist)
            ->andReturn($info);

        $this->mediaMetadataService
            ->shouldNotReceive('tryDownloadArtistImage');

        self::assertSame($info, $this->mediaInformationService->getArtistInformation($artist));
        self::assertNotNull(cache()->get('artist.info.' . $artist->id));
    }

    #[Test]
    public function getArtistInformationTriesDownloadingImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['image' => '']);
        $info = ArtistInformation::make();

        self::assertFalse($artist->has_image);

        $this->encyclopedia
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
