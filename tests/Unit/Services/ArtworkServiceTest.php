<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\ArtworkService;
use App\Services\ImageWriter;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class ArtworkServiceTest extends TestCase
{
    private ImageWriter|MockInterface $imageWriter;
    private Finder|MockInterface $finder;
    private ArtworkService $artworkService;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageWriter = Mockery::mock(ImageWriter::class);
        $this->finder = Mockery::mock(Finder::class);

        $this->artworkService = new ArtworkService($this->imageWriter, $this->finder);
    }

    #[Test]
    public function writeAlbumCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $coverPath = '/koel/public/img/album/foo.jpg';

        $this->imageWriter
            ->expects('write')
            ->with('/koel/public/img/album/foo.jpg', 'dummy-src');

        $this->imageWriter->expects('write');

        $cover = $this->artworkService->storeAlbumCover($album, 'dummy-src', $coverPath);

        self::assertSame(album_cover_url('foo.jpg'), $album->refresh()->cover);
        self::assertSame($cover, $album->cover);
    }

    #[Test]
    public function writeArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $imagePath = '/koel/public/img/artist/foo.jpg';

        $this->imageWriter
            ->expects('write')
            ->with('/koel/public/img/artist/foo.jpg', 'dummy-src');

        $this->artworkService->storeArtistImage($artist, 'dummy-src', $imagePath);

        self::assertSame(artist_image_url('foo.jpg'), $artist->refresh()->image);
    }
}
