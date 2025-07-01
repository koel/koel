<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\ArtworkService;
use App\Services\Contracts\Encyclopedia;
use App\Services\EncyclopediaService;
use App\Services\LastfmService;
use App\Services\SpotifyService;
use App\Values\AlbumInformation;
use App\Values\ArtistInformation;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EncyclopediaServiceTest extends TestCase
{
    private Encyclopedia|MockInterface $encyclopedia;
    private ArtworkService|MockInterface $artworkService;
    private SpotifyService|MockInterface $spotifyService;
    private EncyclopediaService $encyclopediaService;

    public function setUp(): void
    {
        parent::setUp();

        $this->encyclopedia = Mockery::mock(LastfmService::class);
        $this->artworkService = Mockery::mock(ArtworkService::class);
        $this->spotifyService = Mockery::mock(SpotifyService::class);

        $this->encyclopediaService = new EncyclopediaService(
            $this->encyclopedia,
            $this->artworkService,
            $this->spotifyService,
        );
    }

    #[Test]
    public function getAlbumInformation(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $info = AlbumInformation::make();

        $this->encyclopedia
            ->expects('getAlbumInformation')
            ->with($album)
            ->andReturn($info);

        self::assertSame($info, $this->encyclopediaService->getAlbumInformation($album));
        self::assertNotNull(cache()->get(cache_key('album information', $album->name, $album->artist->name)));
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

        $this->artworkService
            ->shouldReceive('storeAlbumCover')
            ->with($album);

        self::assertSame($info, $this->encyclopediaService->getAlbumInformation($album));
    }

    #[Test]
    public function getArtistInformation(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $info = ArtistInformation::make();

        self::assertTrue($artist->has_image);

        $this->encyclopedia
            ->expects('getArtistInformation')
            ->with($artist)
            ->andReturn($info);

        self::assertSame($info, $this->encyclopediaService->getArtistInformation($artist));
        self::assertNotNull(cache()->get(cache_key('artist information', $artist->name)));
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

        self::assertSame($info, $this->encyclopediaService->getArtistInformation($artist));
    }
}
