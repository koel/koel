<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\ImageStorage;
use App\Values\ImageWritingConfig;
use Mockery;
use Mockery\MockInterface;
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

    #[Test]
    public function getAlbumThumbnail(): void
    {
        /** @var Album $createdAlbum */
        $createdAlbum = Album::factory()->create(['cover' => 'foo.jpg']);

        $this->imageStorage
            ->expects('storeImage')
            ->with(
                image_storage_path('foo.jpg'),
                Mockery::on(static fn (ImageWritingConfig $config) => $config->maxWidth === 48),
                image_storage_path('foo_thumb.jpg'),
            )
            ->andReturn('foo_thumb.jpg');

        $response = $this->getAs("api/albums/{$createdAlbum->id}/thumbnail");
        $response->assertJson(['thumbnailUrl' => image_storage_url('foo_thumb.jpg')]);
    }

    #[Test]
    public function getThumbnailForAlbumWithoutCover(): void
    {
        /** @var Album $createdAlbum */
        $createdAlbum = Album::factory()->create(['cover' => '']);
        $this->imageStorage->expects('storeImage')->never();

        $response = $this->getAs("api/albums/{$createdAlbum->id}/thumbnail");
        $response->assertJson(['thumbnailUrl' => null]);
    }
}
