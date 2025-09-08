<?php

namespace Tests\Unit\Services;

use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Services\ImageStorage;
use App\Services\ImageWriter;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class ImageStorageTest extends TestCase
{
    private ImageWriter|MockInterface $imageWriter;
    private Finder|MockInterface $finder;
    private ImageStorage $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageWriter = Mockery::mock(ImageWriter::class);
        $this->finder = Mockery::mock(Finder::class);

        $this->service = new ImageStorage($this->imageWriter, $this->finder);
    }

    #[Test]
    public function storeAlbumCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $coverPath = '/koel/public/img/album/foo.jpg';

        $this->imageWriter
            ->expects('write')
            ->with('/koel/public/img/album/foo.jpg', 'dummy-src');

        $this->imageWriter->expects('write');

        $cover = $this->service->storeAlbumCover($album, 'dummy-src', $coverPath);

        self::assertSame(image_storage_url('foo.jpg'), $album->refresh()->cover);
        self::assertSame($cover, $album->cover);
    }

    #[Test]
    public function storeArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $imagePath = '/koel/public/img/artist/foo.jpg';

        $this->imageWriter
            ->expects('write')
            ->with('/koel/public/img/artist/foo.jpg', 'dummy-src');

        $this->service->storeArtistImage($artist, 'dummy-src', $imagePath);

        self::assertSame(image_storage_url('foo.jpg'), $artist->refresh()->image);
    }

    #[Test]
    public function storeImage(): void
    {
        $ulid = Ulid::freeze();
        $logo = "$ulid.webp";

        $this->imageWriter
            ->expects('write')
            ->with(image_storage_path($logo), 'dummy-logo-src', null);

        self::assertSame($logo, $this->service->storeImage('dummy-logo-src'));
    }
}
