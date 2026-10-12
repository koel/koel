<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageStorageUrlTest extends TestCase
{
    #[Test]
    public function buildTheUrlFromTheApplicationWhenTheDiskHasNoUrl(): void
    {
        config(['filesystems.disks.images.url' => null]);

        self::assertSame(static_url(config('koel.image_storage_dir') . '/cover.webp'), image_storage_url('cover.webp'));
    }

    #[Test]
    public function buildTheUrlFromTheDiskWhenItHasOne(): void
    {
        config(['filesystems.disks.images.url' => 'https://img.example.com/']);

        self::assertSame('https://img.example.com/cover.webp', image_storage_url('cover.webp'));
    }

    #[Test]
    public function includeTheDiskPrefixWhenImagesLiveInABucket(): void
    {
        config([
            'filesystems.disks.images' => [
                'driver' => 's3',
                'root' => 'artwork',
                'key' => 'dummy-key',
                'secret' => 'dummy-secret',
                'region' => 'eu-central-1',
                'bucket' => 'dummy-bucket',
                'url' => 'https://img.example.com',
            ],
        ]);

        self::assertSame('https://img.example.com/artwork/cover.webp', image_storage_url('cover.webp'));
    }

    #[Test]
    public function fallBackWhenThereIsNoFileName(): void
    {
        self::assertNull(image_storage_url(null));
        self::assertSame('default.webp', image_storage_url('', 'default.webp'));
    }
}
