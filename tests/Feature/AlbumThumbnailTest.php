<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\ArtworkService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumThumbnailTest extends TestCase
{
    private ArtworkService|MockInterface $artworkService;

    public function setUp(): void
    {
        parent::setUp();

        $this->artworkService = $this->mock(ArtworkService::class);
    }

    /** @return array<mixed> */
    public static function provideAlbumThumbnailData(): array
    {
        return [['http://localhost/img/covers/foo_thumbnail.jpg'], [null]];
    }

    #[DataProvider('provideAlbumThumbnailData')]
    #[Test]
    public function getAlbumThumbnail(?string $thumbnailUrl): void
    {
        /** @var Album $createdAlbum */
        $createdAlbum = Album::factory()->create();

        $this->artworkService
            ->shouldReceive('getAlbumThumbnailUrl')
            ->once()
            ->with(Mockery::on(static function (Album $album) use ($createdAlbum): bool {
                return $album->id === $createdAlbum->id;
            }))
            ->andReturn($thumbnailUrl);

        $response = $this->getAs("api/albums/{$createdAlbum->public_id}/thumbnail");
        $response->assertJson(['thumbnailUrl' => $thumbnailUrl]);
    }
}
