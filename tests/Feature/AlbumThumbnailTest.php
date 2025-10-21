<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\ImageStorage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumThumbnailTest extends TestCase
{
    private ImageStorage|MockInterface $imageStorage;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageStorage = $this->mock(ImageStorage::class);
    }

    /** @return array<mixed> */
    public static function provideAlbumThumbnailData(): array
    {
        return [['foo_thumbnail.jpg'], [null]];
    }

    #[DataProvider('provideAlbumThumbnailData')]
    #[Test]
    public function getAlbumThumbnail(?string $fileName): void
    {
        /** @var Album $createdAlbum */
        $createdAlbum = Album::factory()->create();

        $this->imageStorage
            ->expects('getOrCreateAlbumThumbnail')
            ->with(Mockery::on(static function (Album $album) use ($createdAlbum): bool {
                return $album->id === $createdAlbum->id;
            }))
            ->andReturn($fileName);

        $response = $this->getAs("api/albums/{$createdAlbum->id}/thumbnail");
        $response->assertJson(['thumbnailUrl' => image_storage_url($fileName)]);
    }
}
