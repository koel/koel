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
        Album::factory()->createMany(10);

        $this->getAs('api/albums')->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs(
            'api/albums?sort=artist_name&order=asc',
        )->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs(
            'api/albums?sort=year&order=desc&page=2',
        )->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs(
            'api/albums?sort=created_at&order=desc&page=1',
        )->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs(
            'api/albums?sort=length&order=desc',
        )->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs(
            'api/albums?sort=rating&order=desc',
        )->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function indexWithCursorReturnsCursorPagination(): void
    {
        Album::factory()->createMany(22);

        $response = $this->getAs(
            'api/albums?cursor=',
        )->assertJsonStructure(AlbumResource::CURSOR_PAGINATION_JSON_STRUCTURE);

        self::assertCount(21, $response->json('data'));
        self::assertNotNull($response->json('meta.next_cursor'));
        self::assertNull($response->json('meta.prev_cursor'));

        $secondPage = $this->getAs(
            'api/albums?cursor=' . $response->json('meta.next_cursor'),
        )->assertJsonStructure(AlbumResource::CURSOR_PAGINATION_JSON_STRUCTURE);

        self::assertCount(1, $secondPage->json('data'));
        self::assertNull($secondPage->json('meta.next_cursor'));
        self::assertNotNull($secondPage->json('meta.prev_cursor'));
    }

    #[Test]
    public function indexWithCursorTraversesAllSupportedSortsWithoutDuplicates(): void
    {
        Album::factory()->createMany(30);

        foreach (['name', 'year', 'created_at', 'artist_name', 'length', 'rating', 'favorite'] as $sort) {
            $allIds = [];
            $cursor = '';
            $pages = 0;

            while ($cursor !== null && $pages < 5) {
                $pages++;
                $r = $this
                    ->getAs("api/albums?favorites_only=false&cursor={$cursor}&sort={$sort}&order=desc")
                    ->assertOk()
                    ->assertJsonStructure(AlbumResource::CURSOR_PAGINATION_JSON_STRUCTURE);

                $allIds = array_merge($allIds, collect($r->json('data'))->pluck('id')->all());
                $cursor = $r->json('meta.next_cursor');
            }

            self::assertCount(30, $allIds, "sort={$sort} returned wrong total");
            self::assertCount(30, array_unique($allIds), "sort={$sort} returned duplicates");
        }
    }

    #[Test]
    public function show(): void
    {
        $this->getAs('api/albums/'
        . Album::factory()->createOne()->id)->assertJsonStructure(AlbumResource::JSON_STRUCTURE);
    }

    #[Test]
    public function updateWithCover(): void
    {
        $album = Album::factory()->createOne();

        $ulid = Ulid::freeze();

        $this
            ->putAs(
                "api/albums/{$album->id}",
                [
                    'name' => 'Updated Album Name',
                    'year' => 2023,
                    'cover' => minimal_base64_encoded_image(),
                ],
                create_admin(),
            )
            ->assertJsonStructure(AlbumResource::JSON_STRUCTURE)
            ->assertOk();

        $album->refresh();

        self::assertEquals('Updated Album Name', $album->name);
        self::assertEquals(2023, $album->year);
        self::assertEquals("$ulid.webp", $album->cover);
    }

    #[Test]
    public function updateKeepingCoverIntact(): void
    {
        $album = Album::factory()->createOne(['cover' => 'neat-cover.webp']);

        $this
            ->putAs(
                "api/albums/{$album->id}",
                [
                    'name' => 'Updated Album Name',
                    'year' => 2023,
                ],
                create_admin(),
            )
            ->assertJsonStructure(AlbumResource::JSON_STRUCTURE)
            ->assertOk();

        self::assertEquals('neat-cover.webp', $album->refresh()->cover);
    }

    #[Test]
    public function updateRemovingCover(): void
    {
        $album = Album::factory()->createOne(['cover' => 'neat-cover.webp']);

        $this
            ->putAs(
                "api/albums/{$album->id}",
                [
                    'name' => 'Updated Album Name',
                    'year' => 2023,
                    'cover' => '',
                ],
                create_admin(),
            )
            ->assertJsonStructure(AlbumResource::JSON_STRUCTURE)
            ->assertOk();

        self::assertEmpty($album->refresh()->cover);
    }

    #[Test]
    public function updatingToExistingNameFails(): void
    {
        $existingAlbum = Album::factory()->createOne(['name' => 'Black Album']);
        $album = Album::factory()->for($existingAlbum->artist)->createOne();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Black Album',
                'year' => 2023,
            ],
            create_admin(),
        )->assertJsonValidationErrors([
            'name' => 'An album with the same name already exists for this artist.',
        ]);
    }

    #[Test]
    public function nonAdminCannotUpdateAlbum(): void
    {
        $album = Album::factory()->createOne();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            create_user(),
        )->assertForbidden();
    }

    #[Test]
    public function showIncludesEditPermissionForAdmin(): void
    {
        $album = Album::factory()->createOne();

        $this->getAs("api/albums/{$album->id}", create_admin())->assertJsonPath('permissions.edit', true);
    }

    #[Test]
    public function showIncludesEditPermissionForRegularUser(): void
    {
        $album = Album::factory()->createOne();

        $this->getAs("api/albums/{$album->id}", create_user())->assertJsonPath('permissions.edit', false);
    }

    #[Test]
    public function showIncludesEditPermissionFalseForUnknownAlbum(): void
    {
        $album = Album::factory()->createOne(['name' => Album::UNKNOWN_NAME]);

        $this->getAs("api/albums/{$album->id}", create_admin())->assertJsonPath('permissions.edit', false);
    }
}
