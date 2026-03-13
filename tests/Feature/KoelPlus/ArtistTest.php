<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class ArtistTest extends PlusTestCase
{
    #[Test]
    public function updateAsOwner(): void
    {
        $artist = Artist::factory()->createOne();

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Updated Artist Name',
            ],
            $artist->user,
        )->assertJsonStructure(ArtistResource::JSON_STRUCTURE);

        $artist->refresh();

        self::assertEquals('Updated Artist Name', $artist->name);
    }

    #[Test]
    public function adminCannotUpdateIfNonOwner(): void
    {
        $artist = Artist::factory()->createOne();
        $scaryBossMan = create_admin();

        self::assertFalse($artist->belongsToUser($scaryBossMan));

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Updated Artist Name',
            ],
            $scaryBossMan,
        )->assertForbidden();
    }

    #[Test]
    public function updateForbiddenForNonOwners(): void
    {
        $artist = Artist::factory()->createOne();
        $randomDude = create_user();

        self::assertFalse($artist->belongsToUser($randomDude));

        $this->putAs(
            "api/artists/{$artist->id}",
            [
                'name' => 'Updated Artist Name',
            ],
            $randomDude,
        )->assertForbidden();
    }
}
