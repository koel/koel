<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumTest extends PlusTestCase
{
    #[Test]
    public function updateAsOwner(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $album->user
        )->assertJsonStructure(AlbumResource::JSON_STRUCTURE);

        $album->refresh();

        self::assertEquals('Updated Album Name', $album->name);
        self::assertEquals(2023, $album->year);
    }

    #[Test]
    public function adminCannotUpdateIfNonOwner(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $scaryBossMan = create_admin();

        self::assertFalse($album->belongsToUser($scaryBossMan));

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $scaryBossMan
        )->assertForbidden();
    }

    #[Test]
    public function updateForbiddenForNonOwners(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $randomDude = create_user();

        self::assertFalse($album->belongsToUser($randomDude));

        $this->putAs(
            "api/albums/{$album->id}",
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $randomDude
        )->assertForbidden();
    }
}
