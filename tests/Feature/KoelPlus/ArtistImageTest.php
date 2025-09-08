<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class ArtistImageTest extends PlusTestCase
{
    #[Test]
    public function artistOwnerCanDeleteImage(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->for($user)->create();

        $this->deleteAs("api/artists/{$artist->id}/image", [], $user)
            ->assertNoContent();
    }

    #[Test]
    public function nonOwnerCannotDeleteImage(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        self::assertFalse($artist->belongsToUser($user));

        $this->deleteAs("api/artists/{$artist->id}/image", [], $user)->assertForbidden();
    }

    #[Test]
    public function evenAdminCannotDeleteImageIfNotOwning(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->deleteAs(
            "api/artists/{$artist->id}/image",
            ['image' => minimal_base64_encoded_image()],
            create_admin(),
        )->assertForbidden();
    }
}
