<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

use function Tests\test_path;

class MediaMetadataServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->cleanUp();
    }

    public function testGetAlbumThumbnailUrl(): void
    {
        File::copy(test_path('blobs/cover.png'), album_cover_path('album-cover-for-thumbnail-test.jpg'));

        /** @var Album $album */
        $album = Album::factory()->create(['cover' => 'album-cover-for-thumbnail-test.jpg']);

        self::assertSame(
            album_cover_url('album-cover-for-thumbnail-test_thumb.jpg'),
            app(MediaMetadataService::class)->getAlbumThumbnailUrl($album)
        );

        self::assertFileExists(album_cover_path('album-cover-for-thumbnail-test_thumb.jpg'));
    }

    public function testGetAlbumThumbnailUrlWithNoCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['cover' => '']);
        self::assertNull(app(MediaMetadataService::class)->getAlbumThumbnailUrl($album));
    }

    private function cleanUp(): void
    {
        File::delete(album_cover_path('album-cover-for-thumbnail-test.jpg'));
        File::delete(album_cover_path('album-cover-for-thumbnail-test_thumb.jpg'));

        self::assertFileDoesNotExist(album_cover_path('album-cover-for-thumbnail-test.jpg'));
        self::assertFileDoesNotExist(album_cover_path('album-cover-for-thumbnail-test_thumb.jpg'));
    }

    protected function tearDown(): void
    {
        $this->cleanUp();

        parent::tearDown();
    }
}
