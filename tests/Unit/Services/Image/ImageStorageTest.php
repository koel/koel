<?php

namespace Tests\Unit\Services\Image;

use App\Helpers\Ulid;
use App\Services\Image\ImageStorage;
use App\Services\Image\ImageWriter;
use App\Services\Image\SvgSanitizer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ImageStorageTest extends TestCase
{
    private ImageWriter|MockInterface $imageWriter;
    private ImageStorage $service;
    private SvgSanitizer|MockInterface $svgSanitizer;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageWriter = Mockery::mock(ImageWriter::class);
        $this->svgSanitizer = Mockery::mock(SvgSanitizer::class);

        $this->service = new ImageStorage($this->imageWriter, $this->svgSanitizer);
    }

    #[Test]
    public function storeRasterImage(): void
    {
        $ulid = Ulid::freeze();
        $logo = "$ulid.avif";

        $disk = Storage::fake(ImageStorage::DISK);

        $this->imageWriter->allows('format')->andReturn('avif');
        $this->imageWriter->expects('encode')->with('dummy-logo-src', null)->andReturn('encoded-bytes');

        self::assertSame($logo, $this->service->storeImage('dummy-logo-src'));
        $disk->assertExists($logo);
        self::assertSame('encoded-bytes', $disk->get($logo));
    }

    #[Test]
    public function storeSvg(): void
    {
        $source = 'data:image/svg+xml;base64,Zm9v';
        $ulid = Ulid::freeze();
        $logo = "$ulid.svg";

        $disk = Storage::fake(ImageStorage::DISK);

        $this->svgSanitizer->expects('sanitize')->with('foo')->andReturn('foo');

        self::assertSame($logo, $this->service->storeImage($source));
        self::assertSame('foo', $disk->get($logo));
    }

    #[Test]
    public function complainWhenAnImageCannotBeDeleted(): void
    {
        Storage::fake(ImageStorage::DISK)->put('cover.webp', 'dummy');

        Storage::shouldReceive('disk')
            ->with(ImageStorage::DISK)
            ->andReturn(Mockery::mock(Filesystem::class, static function (MockInterface $disk): void {
                $disk->allows('exists')->andReturnTrue();
                $disk->expects('delete')->andReturnFalse();
            }));

        $this->expectException(RuntimeException::class);

        $this->service->delete('cover.webp');
    }
}
