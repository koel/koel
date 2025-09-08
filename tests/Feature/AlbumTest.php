<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Http\Resources\AlbumResource;
use App\Models\Album;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class AlbumTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        Album::factory(10)->create();

        $this->getAs('api/albums')
            ->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs('api/albums?sort=artist_name&order=asc')
            ->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs('api/albums?sort=year&order=desc&page=2')
            ->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs('api/albums?sort=created_at&order=desc&page=1')
            ->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function show(): void
    {
        $this->getAs('api/albums/' . Album::factory()->create()->id)
            ->assertJsonStructure(AlbumResource::JSON_STRUCTURE);
    }

    #[Test]
    public function update(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            create_admin()
        )->assertJsonStructure(AlbumResource::JSON_STRUCTURE)
            ->assertOk();

        $album->refresh();

        self::assertEquals('Updated Album Name', $album->name);
        self::assertEquals(2023, $album->year);
    }

    #[Test]
    public function updateWithCover(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $ulid = Ulid::freeze();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
                'cover' => minimal_base64_encoded_image(),
            ],
            create_admin()
        )->assertJsonStructure(AlbumResource::JSON_STRUCTURE)
            ->assertOk();

        $album->refresh();

        self::assertEquals('Updated Album Name', $album->name);
        self::assertEquals(2023, $album->year);
        self::assertEquals(image_storage_url("$ulid.webp"), $album->cover);
    }

    #[Test]
    public function updatingToExistingNameFails(): void
    {
        /** @var Album $existingAlbum */
        $existingAlbum = Album::factory()->create(['name' => 'Black Album']);

        /** @var Album $album */
        $album = Album::factory()->for($existingAlbum->artist)->create();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Black Album',
                'year' => 2023,
            ],
            create_admin()
        )
            ->assertJsonValidationErrors([
                'name' => 'An album with the same name already exists for this artist.',
            ]);
    }

    #[Test]
    public function nonAdminCannotUpdateAlbum(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            create_user()
        )->assertForbidden();
    }
}
