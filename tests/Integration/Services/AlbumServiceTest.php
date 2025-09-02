<?php

namespace Tests\Integration\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Song;
use App\Services\AlbumService;
use App\Values\AlbumUpdateData;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\minimal_base64_encoded_image;

class AlbumServiceTest extends TestCase
{
    private AlbumService $service;

    public function setUp(): void
    {
        parent::setUp();

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
        $updatedAlbum = $this->service->updateAlbum($album, $data);

        self::assertEquals('New Album Name', $updatedAlbum->name);
        self::assertEquals(2023, $updatedAlbum->year);
        self::assertEquals(album_cover_url("$ulid.webp"), $updatedAlbum->cover);

        $songs->each(static function (Song $song) use ($updatedAlbum): void {
            self::assertEquals($updatedAlbum->name, $song->fresh()->album_name);
        });
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
    public function removeCover(): void
    {
        $ulid = Ulid::generate();
        File::put(album_cover_path("$ulid.webp"), 'content');
        File::put(album_cover_path("{$ulid}_thumb.webp"), 'thumb-content');

        /** @var Album $album */
        $album = Album::factory()->create(['cover' => "$ulid.webp"]);

        $this->service->removeAlbumCover($album);

        self::assertNull($album->refresh()->cover);
        self::assertFileDoesNotExist(album_cover_path("$ulid.webp"));
        self::assertFileDoesNotExist(album_cover_path("{$ulid}_thumb.webp"));
    }
}
