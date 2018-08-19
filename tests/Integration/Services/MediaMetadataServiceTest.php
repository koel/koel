<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\MediaMetadataService;
use org\bovigo\vfs\vfsStream;
use Tests\TestCase;

class MediaMetadataServiceTest extends TestCase
{
    /** @var MediaMetadataService */
    private $mediaMetadataService;

    public function setUp()
    {
        parent::setUp();

        $this->mediaMetadataService = new MediaMetadataService();
    }

    public function testCopyAlbumCover()
    {
        /** @var Album $album */
        $album = factory(Album::class)->create();
        $root = vfsStream::setup('home');
        $imageFile = vfsStream::newFile('foo.jpg')->at($root)->setContent('foo');
        $coverPath = vfsStream::url('home/bar.jpg');

        $this->mediaMetadataService->copyAlbumCover($album, $imageFile->url(), $coverPath);

        $this->assertTrue($root->hasChild('bar.jpg'));
        $this->assertEquals('http://localhost/public/img/covers/bar.jpg', Album::find($album->id)->cover);
    }

    public function testWriteAlbumCover()
    {
        /** @var Album $album */
        $album = factory(Album::class)->create();
        $coverContent = 'dummy';
        $root = vfsStream::setup('home');
        $coverPath = vfsStream::url('home/foo.jpg');

        $this->mediaMetadataService->writeAlbumCover($album, $coverContent, 'jpg', $coverPath);

        $this->assertTrue($root->hasChild('foo.jpg'));
        $this->assertEquals('http://localhost/public/img/covers/foo.jpg', Album::find($album->id)->cover);
    }

    public function testWriteArtistImage()
    {
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();
        $imageContent = 'dummy';
        $root = vfsStream::setup('home');
        $imagePath = vfsStream::url('home/foo.jpg');

        $this->mediaMetadataService->writeArtistImage($artist, $imageContent, 'jpg', $imagePath);

        $this->assertTrue($root->hasChild('foo.jpg'));
        $this->assertEquals('http://localhost/public/img/artists/foo.jpg', Artist::find($artist->id)->image);
    }
}
