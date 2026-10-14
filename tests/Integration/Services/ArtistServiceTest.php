<?php

namespace Tests\Integration\Services;

use App\Exceptions\ArtistNameConflictException;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\ArtistService;
use App\Services\FavoriteService;
use App\Services\RatingService;
use App\Values\Artist\ArtistUpdateData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;
use function Tests\stored_image_name;

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
        $artist = Artist::factory()->createOne(['name' => 'Old Artist Name']);

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
        $artist = Artist::factory()->createOne(['name' => 'Old Artist Name']);

        $songs = Song::factory()->for($artist)->count(2)->create();
        $albums = Album::factory()->for($artist)->count(2)->create();

        $data = ArtistUpdateData::make(name: 'New Artist Name', image: minimal_base64_encoded_image());

        $ulid = Ulid::freeze();
        $updatedArtist = $this->service->updateArtist($artist, $data);

        self::assertEquals('New Artist Name', $updatedArtist->name);
        self::assertEquals(stored_image_name($ulid), $updatedArtist->image);

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
        $existingArtist = Artist::factory()->createOne(['name' => 'Existing Artist Name']);
        $artist = Artist::factory()->for($existingArtist->user)->createOne(['name' => 'Old Artist Name']);
        $data = ArtistUpdateData::make(name: 'Existing Artist Name');

        $this->expectException(ArtistNameConflictException::class);

        $this->service->updateArtist($artist, $data);
    }

    #[Test]
    public function albumArtistsShowNewFavoritesAndRatingsWhileCached(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne();
        Album::factory()->for($artist)->createOne();

        self::assertNull($this->service->getAllAlbumArtists($user)->sole()->favorited_at);

        app(FavoriteService::class)->toggleFavorite($artist, $user);
        app(RatingService::class)->setRating($artist, $user, 4);

        $listedArtist = $this->service->getAllAlbumArtists($user)->sole();

        self::assertNotNull($listedArtist->favorited_at);
        self::assertSame(4, (int) $listedArtist->getAttribute('rating'));
    }

    #[Test]
    public function renamingAnArtistRefreshesTheAlbumArtists(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['name' => 'Old Artist Name']);
        Album::factory()->for($artist)->createOne();

        self::assertSame('Old Artist Name', $this->service->getAllAlbumArtists($user)->sole()->name);

        $this->service->updateArtist($artist, ArtistUpdateData::make(name: 'New Artist Name'));

        self::assertSame('New Artist Name', $this->service->getAllAlbumArtists($user)->sole()->name);
    }
}
