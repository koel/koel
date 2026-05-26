<?php

namespace Tests\Unit\Helpers;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageStoragePathTest extends TestCase
{
    #[Test]
    public function createsParentDirectoryIfMissing(): void
    {
        $dir = public_path(config('koel.image_storage_dir'));
        File::deleteDirectory($dir);
        self::assertFalse(File::isDirectory($dir));

        $path = image_storage_path('cover.webp');

        self::assertTrue(File::isDirectory(dirname($path)));
        self::assertSame($dir . DIRECTORY_SEPARATOR . 'cover.webp', $path);
    }

    #[Test]
    public function doesNotCreateDirectoryWhenSkipFlagPassed(): void
    {
        $dir = public_path(config('koel.image_storage_dir'));
        File::deleteDirectory($dir);
        self::assertFalse(File::isDirectory($dir));

        image_storage_path('cover.webp', null, ensureDirectoryExists: false);

        self::assertFalse(File::isDirectory($dir));
    }

    #[Test]
    public function returnsDefaultForEmptyFileName(): void
    {
        self::assertSame('fallback', image_storage_path(null, 'fallback'));
        self::assertNull(image_storage_path(null));
    }
}
