<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumCoverTest extends PlusTestCase
{
    #[Test]
    public function albumOwnerCanDeleteCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->for($user)->create();

        $this->deleteAs("api/albums/{$album->id}/cover", [], $user)
            ->assertNoContent();
    }

    #[Test]
    public function nonOwnerCannotDeleteCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();

        self::assertFalse($album->belongsToUser($user));

        $this->deleteAs("api/albums/{$album->id}/cover", [], $user)->assertForbidden();
    }

    #[Test]
    public function evenAdminsCannotDeleteCoverIfNotOwning(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $admin = create_admin();

        $this->deleteAs("api/albums/{$album->id}/cover", [], $admin)->assertForbidden();
    }
}
