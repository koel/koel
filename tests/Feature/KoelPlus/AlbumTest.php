<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumTest extends PlusTestCase
{
    #[Test]
    public function updateAsCoOwner(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        /** @var Song $song */
        $song = Song::factory()->for($album)->create();

        self::assertTrue($song->owner->isCoOwnerOfAlbum($album));

        $this->putAs(
            'api/albums/' . $album->id,
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $song->owner
        )->assertJsonStructure(AlbumResource::JSON_STRUCTURE);

        $album->refresh();

        $this->assertEquals('Updated Album Name', $album->name);
        $this->assertEquals(2023, $album->year);
    }

    #[Test]
    public function updateAsAdmin(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $scaryBossMan = create_admin();

        self::assertFalse($scaryBossMan->isCoOwnerOfAlbum($album));

        $this->putAs(
            'api/albums/' . $album->id,
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $scaryBossMan
        )->assertJsonStructure(AlbumResource::JSON_STRUCTURE);

        $album->refresh();

        $this->assertEquals('Updated Album Name', $album->name);
        $this->assertEquals(2023, $album->year);
    }

    #[Test]
    public function updateForbiddenForNonOwners(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $randomDude = create_user();

        self::assertFalse($randomDude->isCoOwnerOfAlbum($album));

        $this->putAs(
            'api/albums/' . $album->id,
            [
                'name' => 'Updated Album Name',
                'year' => 2023,
            ],
            $randomDude
        )->assertForbidden();
    }
}
