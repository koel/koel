<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Tests\TestCase;

class MediaMetadataServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->cleanUp();
    }

    public function testGetAlbumThumbnailUrl(): void
    {
        copy(__DIR__ . '/../../blobs/cover.png', $this->coverPath . '/album-cover-for-thumbnail-test.jpg');

        $album = factory(Album::class)->create(['cover' => 'album-cover-for-thumbnail-test.jpg']);

        self::assertSame(
            app()->staticUrl('public/img/covers/album-cover-for-thumbnail-test_thumb.jpg'),
            app()->get(MediaMetadataService::class)->getAlbumThumbnailUrl($album)
        );

        self::assertFileExists($this->coverPath . '/album-cover-for-thumbnail-test_thumb.jpg');
    }

    public function testGetAlbumThumbnailUrlWithNoCover(): void
    {
        $album = factory(Album::class)->create(['cover' => null]);
        self::assertNull(app()->get(MediaMetadataService::class)->getAlbumThumbnailUrl($album));
    }

    private function cleanUp(): void
    {
        @unlink($this->coverPath . '/album-cover-for-thumbnail-test.jpg');
        @unlink($this->coverPath . '/album-cover-for-thumbnail-test_thumb.jpg');
        self::assertFileNotExists($this->coverPath . '/album-cover-for-thumbnail-test.jpg');
        self::assertFileNotExists($this->coverPath . '/album-cover-for-thumbnail-test_thumb.jpg');
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }
}
