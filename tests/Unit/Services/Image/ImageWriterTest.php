<?php

namespace Tests\Unit\Services\Image;

use App\Services\Image\ImageWriter;
use App\Values\ImageWritingConfig;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class ImageWriterTest extends TestCase
{
    #[Test]
    public function doesNotUpscaleImagesNarrowerThanTheMaxWidth(): void
    {
        $source = test_path('fixtures/cover.png');
        $sourceWidth = Image::fromPath($source)->width();

        $destination = sys_get_temp_dir() . '/' . Str::uuid() . '.img';

        (new ImageWriter())->write($destination, $source, ImageWritingConfig::make(maxWidth: $sourceWidth * 2));

        // Re-encode before measuring: the written format may be one that getimagesize() can't read,
        // even when the image driver is perfectly able to encode it.
        self::assertSame($sourceWidth, Image::fromPath($destination)->toPng()->width());

        File::delete($destination);
    }

    #[Test]
    public function retriesAsKoelWhenAHostRefusesTheBrowserUserAgent(): void
    {
        $image = File::get(test_path('fixtures/cover.png'));

        Http::fake(static fn (Request $request) => (
            $request->hasHeader('User-Agent', koel_user_agent()) ? Http::response($image, 200, [
                    'Content-Type' => 'image/png',
                ]) : Http::response('Too Many Requests', 429)
        ));

        $destination = sys_get_temp_dir() . '/' . Str::uuid() . '.img';

        (new ImageWriter())->write($destination, 'https://commons.wikimedia.org/wiki/Special:FilePath/Cover.png');

        self::assertFileExists($destination);
        Http::assertSentCount(2);

        File::delete($destination);
    }

    #[Test]
    public function asksOnlyOnceWhenTheBrowserUserAgentIsServed(): void
    {
        $image = File::get(test_path('fixtures/cover.png'));

        Http::fake(['*' => Http::response($image, 200, ['Content-Type' => 'image/png'])]);

        $destination = sys_get_temp_dir() . '/' . Str::uuid() . '.img';

        (new ImageWriter())->write($destination, 'https://example.com/cover.png');

        self::assertFileExists($destination);
        Http::assertSentCount(1);

        File::delete($destination);
    }
}
