<?php

namespace Tests\Integration\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Song;
use App\Services\AlbumService;
use App\Services\ImageStorage;
use App\Values\Album\AlbumUpdateData;
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
        /** @var Album $album */
        $album = Album::factory()->create([
            'name' => 'Old Album Name',
            'year' => 2020,
        ]);

        $songs = Song::factory()->for($album)->count(2)->create();

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023);

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
        /** @var Album $album */
        $album = Album::factory()->create([
            'name' => 'Old Album Name',
            'year' => 2020,
        ]);

        $songs = Song::factory()->for($album)->count(2)->create();

        $data = AlbumUpdateData::make(
            name: 'New Album Name',
            year: 2023,
            cover: minimal_base64_encoded_image(),
        );

        $ulid = Ulid::freeze();
        $this->imageStorage->expects('storeImage')->with(minimal_base64_encoded_image())->andReturn("$ulid.webp");

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
        /** @var Album $album */
        $album = Album::factory()->create();

        $data = AlbumUpdateData::make(
            name: 'New Album Name',
            year: 2023,
            cover: '',
        );

        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);
        self::assertEmpty($updatedAlbum->cover);
    }

    #[Test]
    public function rejectUpdatingIfArtistAlreadyHasAnAlbumWithTheSameName(): void
    {
        /** @var Album $existingAlbum */
        $existingAlbum = Album::factory()->create(['name' => 'Existing Album Name']);

        /** @var Album $album */
        $album = Album::factory()->for($existingAlbum->artist)->create(['name' => 'Old Album Name']);
        $data = AlbumUpdateData::make(name: 'Existing Album Name', year: 2023);

        $this->expectException(AlbumNameConflictException::class);

        $this->service->updateAlbum($album, $data);
    }

    #[Test]
    public function storeAlbumCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->imageStorage
            ->expects('storeImage')
            ->with('dummy-src')
            ->andReturn('foo.webp');

        $this->service->storeAlbumCover($album, 'dummy-src');

        self::assertSame('foo.webp', $album->refresh()->cover);
    }
}
