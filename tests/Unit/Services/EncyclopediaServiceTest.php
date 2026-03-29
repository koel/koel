<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Contracts\Encyclopedia;
use App\Services\EncyclopediaService;
use App\Services\ImageStorage;
use App\Services\LastfmService;
use App\Services\SpotifyService;
use App\Values\Album\AlbumInformation;
use App\Values\Artist\ArtistInformation;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class EncyclopediaServiceTest extends TestCase
{
    private Encyclopedia|MockInterface $encyclopedia;
    private ImageStorage|MockInterface $imageStorage;
    private SpotifyService|MockInterface $spotifyService;
    private EncyclopediaService $encyclopediaService;

    private array $spotifyConfig;

    public function setUp(): void
    {
        parent::setUp();

        $this->encyclopedia = Mockery::mock(LastfmService::class);
        $this->imageStorage = Mockery::mock(ImageStorage::class);
        $this->spotifyService = Mockery::mock(SpotifyService::class);

        $this->encyclopediaService = new EncyclopediaService(
            $this->encyclopedia,
            $this->imageStorage,
            $this->spotifyService,
        );

        $this->spotifyConfig = config('koel.services.spotify');
    }

    public function tearDown(): void
    {
        config()->set('koel.services.spotify', $this->spotifyConfig);

        parent::tearDown();
    }

    #[Test]
    public function getAlbumInformation(): void
    {
        $album = Album::factory()->createOne();
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
        $album = Album::factory()->createOne(['cover' => '']);
        $info = AlbumInformation::make(cover: 'https://wiki.example.com/album-cover.jpg');

        self::assertEmpty($album->cover);

        $this->encyclopedia
            ->expects('getAlbumInformation')
            ->with($album)
            ->andReturn($info);

        $this->imageStorage->expects('storeImage')->with('https://wiki.example.com/album-cover.jpg');

        self::assertSame($info, $this->encyclopediaService->getAlbumInformation($album));
    }

    #[Test]
    public function getAlbumInformationPrefersSpotifyForFetchingCoverImage(): void
    {
        config()->set('koel.services.spotify', [
            'client_id' => 'spotify-client-id',
            'client_secret' => 'spotify-client-secret',
        ]);
        $album = Album::factory()->createOne(['cover' => '']);
        $info = AlbumInformation::make(cover: 'https://wiki.example.com/album-cover.jpg');

        self::assertEmpty($album->cover);

        $this->encyclopedia
            ->expects('getAlbumInformation')
            ->with($album)
            ->andReturn($info);

        $this->spotifyService
            ->expects('tryGetAlbumCover')
            ->with($album)
            ->andReturn('https://spotify.com/cover.jpg');

        $this->imageStorage->expects('storeImage')->with('https://spotify.com/cover.jpg');

        self::assertSame($info, $this->encyclopediaService->getAlbumInformation($album));
    }

    #[Test]
    public function getArtistInformation(): void
    {
        $artist = Artist::factory()->createOne();
        $info = ArtistInformation::make();

        self::assertNotEmpty($artist->image);

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
        $artist = Artist::factory()->createOne(['image' => '']);
        $info = ArtistInformation::make(image: 'https://wiki.example.com/artist-image.jpg');

        self::assertEmpty($artist->image);

        $this->encyclopedia
            ->expects('getArtistInformation')
            ->with($artist)
            ->andReturn($info);

        $this->imageStorage->expects('storeImage')->with('https://wiki.example.com/artist-image.jpg');

        self::assertSame($info, $this->encyclopediaService->getArtistInformation($artist));
    }

    #[Test]
    public function getArtistInformationPrefersSpotifyForFetchingImage(): void
    {
        config()->set('koel.services.spotify', [
            'client_id' => 'spotify-client-id',
            'client_secret' => 'spotify-client-secret',
        ]);
        $artist = Artist::factory()->createOne(['image' => '']);
        $info = ArtistInformation::make(image: 'https://wiki.example.com/artist-image.jpg');

        self::assertEmpty($artist->image);

        $this->encyclopedia
            ->expects('getArtistInformation')
            ->with($artist)
            ->andReturn($info);

        $this->spotifyService
            ->expects('tryGetArtistImage')
            ->with($artist)
            ->andReturn('https://spotify.com/image.jpg');

        $this->imageStorage->expects('storeImage')->with('https://spotify.com/image.jpg');

        self::assertSame($info, $this->encyclopediaService->getArtistInformation($artist));
    }

    #[Test]
    public function getAlbumInformationGracefullyHandlesCacheFailure(): void
    {
        Cache::shouldReceive('remember')->andThrow(new RuntimeException('file_put_contents failed'));

        $album = Album::factory()->createOne();
        $info = AlbumInformation::make();

        $this->encyclopedia
            ->expects('getAlbumInformation')
            ->with($album)
            ->andReturn($info);

        self::assertSame($info, $this->encyclopediaService->getAlbumInformation($album));
    }

    #[Test]
    public function getArtistInformationGracefullyHandlesCacheFailure(): void
    {
        Cache::shouldReceive('remember')->andThrow(new RuntimeException('file_put_contents failed'));

        $artist = Artist::factory()->createOne(['image' => 'existing.jpg']);
        $info = ArtistInformation::make();

        $this->encyclopedia
            ->expects('getArtistInformation')
            ->with($artist)
            ->andReturn($info);

        self::assertSame($info, $this->encyclopediaService->getArtistInformation($artist));
    }
}
