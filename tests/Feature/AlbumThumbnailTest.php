<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AlbumThumbnailTest extends TestCase
{
    private MockInterface $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    /** @return array<mixed> */
    public static function provideAlbumThumbnailData(): array
    {
        return [['http://localhost/img/covers/foo_thumbnail.jpg'], [null]];
    }

    /** @dataProvider provideAlbumThumbnailData */
    public function testGetAlbumThumbnail(?string $thumbnailUrl): void
    {
        $createdAlbum = Album::factory()->create();

        $this->mediaMetadataService
            ->shouldReceive('getAlbumThumbnailUrl')
            ->once()
            ->with(Mockery::on(static function (Album $album) use ($createdAlbum): bool {
                return $album->id === $createdAlbum->id;
            }))
            ->andReturn($thumbnailUrl);

        $response = $this->getAs("api/albums/{$createdAlbum->id}/thumbnail");
        $response->assertJson(['thumbnailUrl' => $thumbnailUrl]);
    }
}
