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
        Artist::factory(10)->create();

        $this->getAs('api/artists')
            ->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs('api/artists?sort=name&order=asc')
            ->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs('api/artists?sort=created_at&order=desc&page=2')
            ->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function show(): void
    {
        $this->getAs('api/artists/' . Artist::factory()->create()->id)
            ->assertJsonStructure(ArtistResource::JSON_STRUCTURE);
    }

    #[Test]
    public function update(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Updated Artist Name',
            ],
            create_admin()
        )->assertJsonStructure(ArtistResource::JSON_STRUCTURE)
            ->assertOk();

        $artist->refresh();

        self::assertEquals('Updated Artist Name', $artist->name);
    }

    #[Test]
    public function updateWithImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $ulid = Ulid::freeze();

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Updated Artist Name',
                'image' => minimal_base64_encoded_image(),
            ],
            create_admin()
        )->assertJsonStructure(ArtistResource::JSON_STRUCTURE)
            ->assertOk();

        $artist->refresh();

        self::assertEquals('Updated Artist Name', $artist->name);
        self::assertEquals(image_storage_url("$ulid.webp"), $artist->image);
    }

    #[Test]
    public function updatingToExistingNameFails(): void
    {
        /** @var Artist $existingArtist */
        $existingArtist = Artist::factory()->create(['name' => 'Maydup Nem']);

        /** @var Artist $artist */
        $artist = Artist::factory()->for($existingArtist->user)->create();

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Maydup Nem',
            ],
            create_admin()
        )
            ->assertJsonValidationErrors([
                'name' => 'An artist with the same name already exists.',
            ]);
    }

    #[Test]
    public function nonAdminCannotUpdateArtist(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Updated Name',
            ],
            create_user()
        )->assertForbidden();
    }
}
