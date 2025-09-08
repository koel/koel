<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Services\ImageStorage;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class EncyclopediaServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->cleanUp();
    }

    #[Test]
    public function getAlbumThumbnailUrl(): void
    {
        File::copy(test_path('fixtures/cover.png'), image_storage_path('album-cover-for-thumbnail-test.jpg'));

        /** @var Album $album */
        $album = Album::factory()->create(['cover' => 'album-cover-for-thumbnail-test.jpg']);

        self::assertSame(
            image_storage_url('album-cover-for-thumbnail-test_thumb.jpg'),
            app(ImageStorage::class)->getAlbumThumbnailUrl($album)
        );

        self::assertFileExists(image_storage_path('album-cover-for-thumbnail-test_thumb.jpg'));
    }

    #[Test]
    public function getAlbumThumbnailUrlWithNoCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['cover' => '']);
        self::assertNull(app(ImageStorage::class)->getAlbumThumbnailUrl($album));
    }

    private function cleanUp(): void
    {
        File::delete(image_storage_path('album-cover-for-thumbnail-test.jpg'));
        File::delete(image_storage_path('album-cover-for-thumbnail-test_thumb.jpg'));

        self::assertFileDoesNotExist(image_storage_path('album-cover-for-thumbnail-test.jpg'));
        self::assertFileDoesNotExist(image_storage_path('album-cover-for-thumbnail-test_thumb.jpg'));
    }

    protected function tearDown(): void
    {
        $this->cleanUp();

        parent::tearDown();
    }
}
