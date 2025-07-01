<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Services\ArtworkService;
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
        File::copy(test_path('fixtures/cover.png'), album_cover_path('album-cover-for-thumbnail-test.jpg'));

        /** @var Album $album */
        $album = Album::factory()->create(['cover' => 'album-cover-for-thumbnail-test.jpg']);

        self::assertSame(
            album_cover_url('album-cover-for-thumbnail-test_thumb.jpg'),
            app(ArtworkService::class)->getAlbumThumbnailUrl($album)
        );

        self::assertFileExists(album_cover_path('album-cover-for-thumbnail-test_thumb.jpg'));
    }

    #[Test]
    public function getAlbumThumbnailUrlWithNoCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['cover' => '']);
        self::assertNull(app(ArtworkService::class)->getAlbumThumbnailUrl($album));
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
