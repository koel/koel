<?php

namespace Tests\Integration\Services;

use App\Enums\ImageChangeType;
use App\Exceptions\AlbumNameConflictException;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Song;
use App\Services\AlbumService;
use App\Services\Image\ImageStorage;
use App\Values\Album\AlbumUpdateData;
use App\Values\ImageChange;
use App\Values\ImageWritingConfig;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\minimal_base64_encoded_image;

class AlbumServiceTest extends TestCase
{
    private AlbumService $service;
    private ImageStorage|MockInterface $imageStorage;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageStorage = $this->mock(ImageStorage::class);
        $this->service = app(AlbumService::class);
    }

    #[Test]
    public function updateAlbum(): void
    {
        $album = Album::factory()->createOne([
            'name' => 'Old Album Name',
            'year' => 2020,
        ]);

        $songs = Song::factory()->for($album)->count(2)->create();

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023);

        $this->imageStorage
            ->expects('storeOptionalImage')
            ->with(null)
            ->andReturn(ImageChange::make(ImageChangeType::KEEP));

        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);

        $songs->each(static function (Song $song) use ($updatedAlbum): void {
            self::assertEquals($updatedAlbum->name, $song->fresh()->album_name);
        });
    }

    #[Test]
    public function updateAlbumWithCover(): void
    {
        $album = Album::factory()->createOne([
            'name' => 'Old Album Name',
            'year' => 2020,
        ]);

        $songs = Song::factory()->for($album)->count(2)->create();

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023, cover: minimal_base64_encoded_image());

        $ulid = Ulid::freeze();
        $this->imageStorage
            ->expects('storeOptionalImage')
            ->with(minimal_base64_encoded_image())
            ->andReturn(ImageChange::make(ImageChangeType::REPLACE, "$ulid.webp"));

        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);
        self::assertEquals("$ulid.webp", $updatedAlbum->cover);

        $songs->each(static function (Song $song) use ($updatedAlbum): void {
            self::assertEquals($updatedAlbum->name, $song->fresh()->album_name);
        });
    }

    #[Test]
    public function updateAlbumRemovingCover(): void
    {
        $album = Album::factory()->createOne();

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023, cover: '');

        $this->imageStorage
            ->expects('storeOptionalImage')
            ->with('')
            ->andReturn(ImageChange::make(ImageChangeType::REMOVE));

        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);
        self::assertEmpty($updatedAlbum->cover);
    }

    #[Test]
    public function rejectUpdatingIfArtistAlreadyHasAnAlbumWithTheSameName(): void
    {
        $existingAlbum = Album::factory()->createOne(['name' => 'Existing Album Name']);
        $album = Album::factory()->for($existingAlbum->artist)->createOne(['name' => 'Old Album Name']);
        $data = AlbumUpdateData::make(name: 'Existing Album Name', year: 2023);

        $this->expectException(AlbumNameConflictException::class);

        $this->service->updateAlbum($album, $data);
    }

    #[Test]
    public function storeAlbumCover(): void
    {
        $album = Album::factory()->createOne();

        $this->imageStorage->expects('storeImage')->with('dummy-src')->andReturn('foo.webp');

        $this->service->storeAlbumCover($album, 'dummy-src');

        self::assertSame('foo.webp', $album->refresh()->cover);
    }

    #[Test]
    public function generateAlbumSmallCover(): void
    {
        $album = Album::factory()->createOne(['cover' => 'cover.webp', 'small_cover' => null]);

        $this->imageStorage->expects('get')->with('cover.webp')->andReturn('cover-bytes');

        $this->imageStorage
            ->expects('storeImage')
            ->with('cover-bytes', Mockery::on(static fn (ImageWritingConfig $config) => $config->maxWidth === 144))
            ->andReturn('small.webp');

        $this->service->generateAlbumSmallCover($album);

        self::assertSame('small.webp', $album->refresh()->small_cover);
    }
}
