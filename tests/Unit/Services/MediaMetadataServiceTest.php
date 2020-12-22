<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\ImageWriter;
use App\Services\MediaMetadataService;
use Illuminate\Log\Logger;
use Mockery;
use Tests\TestCase;

class MediaMetadataServiceTest extends TestCase
{
    private $mediaMetadataService;
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
        $album = Album::factory()->create();
        $coverContent = 'dummy';
        $coverPath = '/koel/public/img/album/foo.jpg';

        $this->imageWriter
            ->shouldReceive('writeFromBinaryData')
            ->once()
            ->with('/koel/public/img/album/foo.jpg', 'dummy');

        $this->mediaMetadataService->writeAlbumCover($album, $coverContent, 'jpg', $coverPath);
        self::assertEquals(album_cover_url('foo.jpg'), Album::find($album->id)->cover);
    }

    public function testWriteArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $imageContent = 'dummy';
        $imagePath = '/koel/public/img/artist/foo.jpg';

        $this->imageWriter
            ->shouldReceive('writeFromBinaryData')
            ->once()
            ->with('/koel/public/img/artist/foo.jpg', 'dummy');

        $this->mediaMetadataService->writeArtistImage($artist, $imageContent, 'jpg', $imagePath);
        self::assertEquals(artist_image_url('foo.jpg'), Artist::find($artist->id)->image);
    }
}
