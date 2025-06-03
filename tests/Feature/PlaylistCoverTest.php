<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;
use function Tests\read_as_data_url;
use function Tests\test_path;

class PlaylistCoverTest extends TestCase
{
    #[Test]
    public function uploadCover(): void
    {
        $playlist = create_playlist();
        self::assertNull($playlist->cover);

        $this->putAs(
            "api/playlists/{$playlist->id}/cover",
            ['cover' => read_as_data_url(test_path('blobs/cover.png'))],
            $playlist->owner
        )
            ->assertOk();

        self::assertNotNull($playlist->refresh()->cover);
    }

    #[Test]
    public function uploadCoverNotAllowedForNonOwner(): void
    {
        $playlist = create_playlist();

        $this->putAs(
            "api/playlists/{$playlist->id}/cover",
            ['cover' => 'data:image/jpeg;base64,Rm9v'],
            create_user()
        )
            ->assertForbidden();
    }

    #[Test]
    public function deleteCover(): void
    {
        $playlist = create_playlist(['cover' => 'cover.jpg']);

        $this->deleteAs("api/playlists/{$playlist->id}/cover", [], $playlist->owner)
            ->assertNoContent();

        self::assertNull($playlist->refresh()->cover);
    }

    #[Test]
    public function nonOwnerCannotDeleteCover(): void
    {
        $playlist = create_playlist(['cover' => 'cover.jpg']);

        $this->deleteAs("api/playlists/{$playlist->id}/cover", [], create_user())
            ->assertForbidden();

        self::assertSame('cover.jpg', $playlist->refresh()->getRawOriginal('cover'));
    }
}
