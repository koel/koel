<?php

namespace Tests\Unit\Services\Image;

use App\Services\Image\ImageWriter;
use App\Values\ImageWritingConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class ImageWriterTest extends TestCase
{
    #[Test]
    public function doesNotUpscaleImagesNarrowerThanTheMaxWidth(): void
    {
        $source = test_path('fixtures/cover.png');
        $sourceWidth = Image::read($source)->width();

        $destination = sys_get_temp_dir() . '/' . Str::uuid() . '.img';

        (new ImageWriter())->write($destination, $source, ImageWritingConfig::make(maxWidth: $sourceWidth * 2));

        self::assertSame($sourceWidth, Image::read($destination)->width());

        File::delete($destination);
    }
}
