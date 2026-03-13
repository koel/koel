<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
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
}
