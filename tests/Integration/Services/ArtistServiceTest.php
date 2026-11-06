<?php

namespace Tests\Integration\Services;

use App\Exceptions\ArtistNameConflictException;
use App\Helpers\Ulid;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\Song;
use App\Services\ArtistService;
use App\Values\Artist\ArtistUpdateData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
        $data = ArtistUpdateData::make(name: 'existing artist name');

        $this->expectException(ArtistNameConflictException::class);

        $this->service->updateArtist($artist, $data);
    }

    #[Test]
    public function rejectUpdatingIfAnotherUserOwnsAnArtistWithTheSameName(): void
    {
        Artist::factory()->createOne(['name' => 'Existing Artist Name']);
        $artist = Artist::factory()->createOne(['name' => 'Old Artist Name']);

        $this->expectException(ArtistNameConflictException::class);

        $this->service->updateArtist($artist, ArtistUpdateData::make(name: 'Existing Artist Name'));
    }

    #[Test]
    public function rejectUpdatingToTheExactNameOfAnArtistWithNonAsciiCharacters(): void
    {
        $existingArtist = Artist::factory()->createOne(['name' => 'Ünder Nem']);
        $artist = Artist::factory()->for($existingArtist->user)->createOne(['name' => 'Old Artist Name']);

        $this->expectException(ArtistNameConflictException::class);

        $this->service->updateArtist($artist, ArtistUpdateData::make(name: 'Ünder Nem'));
    }

    #[Test]
    public function rejectUpdatingToTheNameOfAnArtistThatDiffersOnlyInCase(): void
    {
        $artist = Artist::factory()->createOne(['name' => 'maydup nem']);
        Artist::factory()->for($artist->user)->createOne(['name' => 'Maydup Nem']);

        $this->expectException(ArtistNameConflictException::class);

        $this->service->updateArtist($artist, ArtistUpdateData::make(name: 'Maydup Nem'));
    }

    #[Test]
    public function mergeArtists(): void
    {
        $target = Artist::factory()->createOne(['name' => 'Maydup Nem']);
        $source = Artist::factory()->for($target->user)->createOne(['name' => 'maydup nem']);
        $targetAlbum = Album::factory()->for($target)->createOne(['name' => 'Greatest Hits']);
        $namesakeAlbum = Album::factory()->for($source)->createOne(['name' => 'greatest hits']);
        $movingAlbum = Album::factory()->for($source)->createOne(['name' => 'Live']);
        $namesakeAlbumSong = Song::factory()->for($namesakeAlbum)->for($source)->createOne();
        $featuredSong = Song::factory()->for($source)->createOne();
        $favorite = Favorite::factory()->for($source, 'favoriteable')->createOne();
        $rating = Rating::factory()->for($source, 'rateable')->createOne();

        $mergedArtist = $this->service->mergeArtists($source, $target);

        self::assertTrue($mergedArtist->is($target));
        self::assertModelMissing($source);
        self::assertModelMissing($namesakeAlbum);

        $movingAlbum->refresh();
        self::assertTrue($movingAlbum->artist->is($target));
        self::assertSame('Maydup Nem', $movingAlbum->artist_name);

        $namesakeAlbumSong->refresh();
        self::assertTrue($namesakeAlbumSong->album->is($targetAlbum));
        self::assertTrue($namesakeAlbumSong->artist->is($target));

        $featuredSong->refresh();
        self::assertTrue($featuredSong->artist->is($target));
        self::assertSame('Maydup Nem', $featuredSong->artist_name);

        self::assertTrue($target->favorites()->whereKey($favorite->id)->exists());
        self::assertTrue($target->ratings()->whereKey($rating->id)->exists());
    }
}
