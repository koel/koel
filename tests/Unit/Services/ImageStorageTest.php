<?php

namespace Tests\Unit\Services;

use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Services\ImageStorage;
use App\Services\ImageWriter;
use App\Services\SvgSanitizer;
use Illuminate\Support\Facades\File;
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
    private SvgSanitizer|MockInterface $svgSantizer;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageWriter = Mockery::mock(ImageWriter::class);
        $this->svgSantizer = Mockery::mock(SvgSanitizer::class);
        $this->finder = Mockery::mock(Finder::class);

        $this->service = new ImageStorage($this->imageWriter, $this->svgSantizer, $this->finder);
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
    public function storeRasterImage(): void
    {
        $ulid = Ulid::freeze();
        $logo = "$ulid.webp";

        $this->imageWriter
            ->expects('write')
            ->with(image_storage_path($logo), 'dummy-logo-src', null);

        self::assertSame($logo, $this->service->storeImage('dummy-logo-src'));
    }

    #[Test]
    public function storeSvg(): void
    {
        $source = 'data:image/svg+xml;base64,Zm9v';
        $ulid = Ulid::freeze();
        $logo = "$ulid.svg";

        $this->svgSantizer
            ->expects('sanitize')
            ->with('foo')
            ->andReturn('foo');

        File::expects('put')
            ->with(image_storage_path($logo), 'foo');

        self::assertSame($logo, $this->service->storeImage($source));
    }
}
