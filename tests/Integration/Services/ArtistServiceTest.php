<?php

namespace Tests\Integration\Services;

use App\Exceptions\ArtistNameConflictException;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\ArtistService;
use App\Values\Artist\ArtistUpdateData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\minimal_base64_encoded_image;

class ArtistServiceTest extends TestCase
{
    private ArtistService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(ArtistService::class);
    }

    #[Test]
    public function updateArtist(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Old Artist Name']);

        $songs = Song::factory()->for($artist)->count(2)->create();
        $albums = Album::factory()->for($artist)->count(2)->create();

        $data = ArtistUpdateData::make(name: 'New Artist Name');

        $updatedArtist = $this->service->updateArtist($artist, $data);

        self::assertEquals('New Artist Name', $updatedArtist->name);

        $songs->each(static function (Song $song) use ($updatedArtist): void {
            self::assertEquals($updatedArtist->name, $song->fresh()->artist_name);
        });

        $albums->each(static function (Album $album) use ($updatedArtist): void {
            self::assertEquals($updatedArtist->name, $album->fresh()->artist_name);
        });
    }

    #[Test]
    public function updateArtistWithImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Old Artist Name']);

        $songs = Song::factory()->for($artist)->count(2)->create();
        $albums = Album::factory()->for($artist)->count(2)->create();

        $data = ArtistUpdateData::make(
            name: 'New Artist Name',
            image: minimal_base64_encoded_image(),
        );

        $ulid = Ulid::freeze();
        $updatedArtist = $this->service->updateArtist($artist, $data);

        self::assertEquals('New Artist Name', $updatedArtist->name);
        self::assertEquals(image_storage_url("$ulid.webp"), $updatedArtist->image);

        $songs->each(static function (Song $song) use ($updatedArtist): void {
            self::assertEquals($updatedArtist->name, $song->fresh()->artist_name);
        });

        $albums->each(static function (Album $album) use ($updatedArtist): void {
            self::assertEquals($updatedArtist->name, $album->fresh()->artist_name);
        });
    }

    #[Test]
    public function rejectUpdatingIfArtistAlreadyExistsForUser(): void
    {
        /** @var Artist $existingArtist */
        $existingArtist = Artist::factory()->create(['name' => 'Existing Artist Name']);

        /** @var Artist $artist */
        $artist = Artist::factory()->for($existingArtist->user)->create(['name' => 'Old Artist Name']);
        $data = ArtistUpdateData::make(name: 'Existing Artist Name');

        $this->expectException(ArtistNameConflictException::class);

        $this->service->updateArtist($artist, $data);
    }
}
