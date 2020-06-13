<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use function App\Helpers\album_cover_url;

class AlbumThumbnailTest extends TestCase
{
    /**
     * @var MediaMetadataService|MockInterface
     */
    private $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();
        $this->mediaMetadataService = self::mockIocDependency(MediaMetadataService::class);
    }

    public function provideAlbumThumbnailData(): array
    {
        return [['http://localhost/public/img/covers/foo_thumbnail.jpg'], [null]];
    }

    /**
     * @dataProvider provideAlbumThumbnailData
     */
    public function testGetAlbumThumbnail(?string $thumbnailUrl): void
    {
        /** @var Album $createdAlbum */
        $createdAlbum = factory(Album::class)->create();

        $this->mediaMetadataService
            ->shouldReceive('getAlbumThumbnailUrl')
            ->once()
            ->with(Mockery::on(static function (Album $album) use ($createdAlbum): bool {
                return $album->id === $createdAlbum->id;
            }))
            ->andReturn($thumbnailUrl);

        $this->getAsUser("api/album/{$createdAlbum->id}/thumbnail")
            ->seeJson(['thumbnailUrl' => $thumbnailUrl]);
    }
}
