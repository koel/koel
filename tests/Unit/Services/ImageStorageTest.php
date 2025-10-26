<?php

namespace Tests\Unit\Services;

use App\Helpers\Ulid;
use App\Services\ImageStorage;
use App\Services\ImageWriter;
use App\Services\SvgSanitizer;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
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

        $this->svgSanitizer
            ->expects('sanitize')
            ->with('foo')
            ->andReturn('foo');

        File::expects('put')
            ->with(image_storage_path($logo), 'foo');

        self::assertSame($logo, $this->service->storeImage($source));
    }
}
