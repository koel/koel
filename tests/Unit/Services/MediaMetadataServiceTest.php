<?php

namespace Tests\Unit\Services;

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
        $this->assertEquals('http://localhost/public/img/covers/foo.jpg', Album::find($album->id)->cover);
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
        $this->assertEquals('http://localhost/public/img/artists/foo.jpg', Artist::find($artist->id)->image);
    }
}
