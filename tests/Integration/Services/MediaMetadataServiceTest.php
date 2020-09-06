<?php

namespace Tests\Integration\Services;

use function App\Helpers\album_cover_path;
use function App\Helpers\album_cover_url;
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
        copy(__DIR__.'/../../blobs/cover.png', album_cover_path('album-cover-for-thumbnail-test.jpg'));

        $album = factory(Album::class)->create(['cover' => 'album-cover-for-thumbnail-test.jpg']);

        self::assertSame(
            album_cover_url('album-cover-for-thumbnail-test_thumb.jpg'),
            app(MediaMetadataService::class)->getAlbumThumbnailUrl($album)
        );

        self::assertFileExists(album_cover_path('album-cover-for-thumbnail-test_thumb.jpg'));
    }

    public function testGetAlbumThumbnailUrlWithNoCover(): void
    {
        $album = factory(Album::class)->create(['cover' => null]);
        self::assertNull(app(MediaMetadataService::class)->getAlbumThumbnailUrl($album));
    }

    private function cleanUp(): void
    {
        @unlink(album_cover_path('album-cover-for-thumbnail-test.jpg'));
        @unlink(album_cover_path('album-cover-for-thumbnail-test_thumb.jpg'));
        self::assertFileNotExists(album_cover_path('album-cover-for-thumbnail-test.jpg'));
        self::assertFileNotExists(album_cover_path('album-cover-for-thumbnail-test_thumb.jpg'));
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }
}
