<?php

namespace Tests\Unit\Services;

use function App\Helpers\album_cover_url;
use function App\Helpers\artist_image_url;
use App\Models\Album;
use App\Models\Artist;
use App\Services\ImageWriter;
use App\Services\MediaMetadataService;
use Illuminate\Log\Logger;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MediaMetadataServiceTest extends TestCase
{
    /** @var MediaMetadataService */
    private $mediaMetadataService;

    /** @var ImageWriter|MockInterface */
    private $imageWriter;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageWriter = Mockery::mock(ImageWriter::class);
        $this->mediaMetadataService = new MediaMetadataService($this->imageWriter, app(Logger::class));
    }

    public function testWriteAlbumCover(): void
    {
        /** @var Album $album */
        $album = factory(Album::class)->create();
        $coverContent = 'dummy';
        $coverPath = '/koel/public/images/album/foo.jpg';

        $this->imageWriter
            ->shouldReceive('writeFromBinaryData')
            ->once()
            ->with('/koel/public/images/album/foo.jpg', 'dummy');

        $this->mediaMetadataService->writeAlbumCover($album, $coverContent, 'jpg', $coverPath);
        self::assertEquals(album_cover_url('foo.jpg'), Album::find($album->id)->cover);
    }

    public function testWriteArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();
        $imageContent = 'dummy';
        $imagePath = '/koel/public/images/artist/foo.jpg';

        $this->imageWriter
            ->shouldReceive('writeFromBinaryData')
            ->once()
            ->with('/koel/public/images/artist/foo.jpg', 'dummy');

        $this->mediaMetadataService->writeArtistImage($artist, $imageContent, 'jpg', $imagePath);
        self::assertEquals(artist_image_url('foo.jpg'), Artist::find($artist->id)->image);
    }
}
