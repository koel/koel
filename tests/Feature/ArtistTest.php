<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Http\Resources\ArtistResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Embed;
use App\Models\Song;
use App\Values\EmbedOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class ArtistTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        Artist::factory()->createMany(10);

        $this->getAs('api/artists')->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs('api/artists?sort=name&order=asc')->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs(
            'api/artists?sort=created_at&order=desc&page=2',
        )->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function indexExcludesArtistsWithoutAlbums(): void
    {
        $albumArtist = Artist::factory()->createOne();
        Album::factory()->for($albumArtist)->createOne();

        $perTrackOnly = Artist::factory()->createOne();

        $ids = collect($this->getAs('api/artists')->json('data'))->pluck('id')->all();

        self::assertContains($albumArtist->id, $ids);
        self::assertNotContains($perTrackOnly->id, $ids);
    }

    #[Test]
    public function indexExcludesFeaturedArtistsOnSomeoneElsesAlbum(): void
    {
        // Album owned by `albumOwner`, with a track credited to `featuredArtist`.
        // featuredArtist has no album of their own → must be excluded.
        $albumOwner = Artist::factory()->createOne();
        $album = Album::factory()->for($albumOwner)->createOne();

        $featuredArtist = Artist::factory()->createOne();
        Song::factory()
            ->for($album)
            ->for($featuredArtist)
            ->createOne();

        $ids = collect($this->getAs('api/artists')->json('data'))->pluck('id')->all();

        self::assertContains($albumOwner->id, $ids);
        self::assertNotContains($featuredArtist->id, $ids);
    }

    #[Test]
    public function indexIncludesCompilationCuratorEvenWithoutOwnTracks(): void
    {
        // The curator owns a compilation album but contributes no tracks themselves;
        // every song on the comp is credited to a different track artist. Curator
        // is still the album_artist → must be included. Track artist with no album
        // of their own → excluded.
        $curator = Artist::factory()->createOne();
        $compilation = Album::factory()->for($curator)->createOne();

        $trackArtist = Artist::factory()->createOne();
        Song::factory()
            ->for($compilation)
            ->for($trackArtist)
            ->createOne();

        $ids = collect($this->getAs('api/artists')->json('data'))->pluck('id')->all();

        self::assertContains($curator->id, $ids);
        self::assertNotContains($trackArtist->id, $ids);
    }

    #[Test]
    public function show(): void
    {
        $this->getAs('api/artists/'
        . Artist::factory()->createOne()->id)->assertJsonStructure(ArtistResource::JSON_STRUCTURE);
    }

    #[Test]
    public function updateWithImage(): void
    {
        $artist = Artist::factory()->createOne();

        $ulid = Ulid::freeze();

        $this
            ->putAs(
                "api/artists/{$artist->id}",
                [
                    'name' => 'Updated Artist Name',
                    'image' => minimal_base64_encoded_image(),
                ],
                create_admin(),
            )
            ->assertJsonStructure(ArtistResource::JSON_STRUCTURE)
            ->assertOk();

        $artist->refresh();

        self::assertEquals('Updated Artist Name', $artist->name);
        self::assertEquals("$ulid.webp", $artist->image);
    }

    #[Test]
    public function updateKeepingImageIntact(): void
    {
        $artist = Artist::factory()->createOne(['image' => 'neat-pose.webp']);

        $this
            ->putAs(
                "api/artists/{$artist->id}",
                [
                    'name' => 'Updated Artist Name',
                ],
                create_admin(),
            )
            ->assertJsonStructure(ArtistResource::JSON_STRUCTURE)
            ->assertOk();

        self::assertEquals('neat-pose.webp', $artist->refresh()->image);
    }

    #[Test]
    public function updateRemovingImage(): void
    {
        $artist = Artist::factory()->createOne(['image' => 'neat-pose.webp']);

        $this
            ->putAs(
                "api/artists/{$artist->id}",
                [
                    'name' => 'Updated Artist Name',
                    'image' => '',
                ],
                create_admin(),
            )
            ->assertJsonStructure(ArtistResource::JSON_STRUCTURE)
            ->assertOk();

        self::assertEmpty($artist->refresh()->image);
    }

    #[Test]
    public function updatingToExistingNameFails(): void
    {
        $existingArtist = Artist::factory()->createOne(['name' => 'Maydup Nem']);
        $artist = Artist::factory()->for($existingArtist->user)->createOne();

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Maydup Nem',
            ],
            create_admin(),
        )->assertJsonValidationErrors([
            'name' => 'An artist with the same name already exists.',
        ]);
    }

    #[Test]
    public function nonAdminCannotUpdateArtist(): void
    {
        $artist = Artist::factory()->createOne();

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Updated Name',
            ],
            create_user(),
        )->assertForbidden();
    }

    #[Test]
    public function showIncludesEditPermissionForAdmin(): void
    {
        $artist = Artist::factory()->createOne();

        $this->getAs("api/artists/{$artist->id}", create_admin())->assertJsonPath('permissions.edit', true);
    }

    #[Test]
    public function showIncludesEditPermissionForRegularUser(): void
    {
        $artist = Artist::factory()->createOne();

        $this->getAs("api/artists/{$artist->id}", create_user())->assertJsonPath('permissions.edit', false);
    }

    #[Test]
    public function showIncludesEditPermissionFalseForUnknownArtist(): void
    {
        $artist = Artist::factory()->createOne(['name' => Artist::UNKNOWN_NAME]);

        $this->getAs("api/artists/{$artist->id}", create_admin())->assertJsonPath('permissions.edit', false);
    }

    #[Test]
    public function showIncludesEditPermissionFalseForVariousArtists(): void
    {
        $artist = Artist::factory()->createOne(['name' => Artist::VARIOUS_NAME]);

        $this->getAs("api/artists/{$artist->id}", create_admin())->assertJsonPath('permissions.edit', false);
    }

    #[Test]
    public function embedPayloadOmitsPermissions(): void
    {
        $artist = Artist::factory()->createOne();
        $embed = Embed::factory()->createOne([
            'embeddable_type' => 'artist',
            'embeddable_id' => $artist->id,
        ]);

        $this
            ->getJson("api/embeds/{$embed->id}/" . EmbedOptions::make())
            ->assertSuccessful()
            ->assertJsonMissingPath('embed.embeddable.permissions');
    }
}
