<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\ImageWriter;
use App\Services\MediaMetadataService;
use App\Services\SpotifyService;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class MediaMetadataServiceTest extends TestCase
{
    private LegacyMockInterface|SpotifyService|MockInterface $spotifyService;
    private LegacyMockInterface|ImageWriter|MockInterface $imageWriter;
    private MediaMetadataService $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->spotifyService = Mockery::mock(SpotifyService::class);
        $this->imageWriter = Mockery::mock(ImageWriter::class);

        $this->mediaMetadataService = new MediaMetadataService(
            $this->spotifyService,
            $this->imageWriter,
            app(LoggerInterface::class)
        );
    }

    public function testTryDownloadAlbumCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['cover' => '']);

        $this->spotifyService
            ->shouldReceive('tryGetAlbumCover')
            ->with($album)
            ->andReturn('/dev/null/cover.jpg');

        $this->mediaMetadataService->tryDownloadAlbumCover($album);
    }

    public function testWriteAlbumCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $coverPath = '/koel/public/img/album/foo.jpg';

        $this->imageWriter
            ->shouldReceive('write')
            ->once()
            ->with('/koel/public/img/album/foo.jpg', 'dummy-src');

        $this->mediaMetadataService->writeAlbumCover($album, 'dummy-src', 'jpg', $coverPath);
        self::assertEquals(album_cover_url('foo.jpg'), Album::find($album->id)->cover);
    }

    public function testTryDownloadArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['image' => '']);

        $this->spotifyService
            ->shouldReceive('tryGetArtistImage')
            ->with($artist)
            ->andReturn('/dev/null/img.jpg');

        $this->mediaMetadataService->tryDownloadArtistImage($artist);
    }

    public function testWriteArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $imagePath = '/koel/public/img/artist/foo.jpg';

        $this->imageWriter
            ->shouldReceive('write')
            ->once()
            ->with('/koel/public/img/artist/foo.jpg', 'dummy-src');

        $this->mediaMetadataService->writeArtistImage($artist, 'dummy-src', 'jpg', $imagePath);
        self::assertEquals(artist_image_url('foo.jpg'), Artist::find($artist->id)->image);
    }
}
