<?php

namespace Tests\Feature;

use App\Models\Playlist;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\read_as_data_url;
use function Tests\test_path;

class PlaylistCoverTest extends TestCase
{
    public function testUploadCover(): void
    {
        $playlist = Playlist::factory()->create();
        self::assertNull($playlist->cover);

        $this->putAs(
            "api/playlists/$playlist->id/cover",
            ['cover' => read_as_data_url(test_path('blobs/cover.png'))],
            $playlist->user
        )
            ->assertOk();

        self::assertNotNull($playlist->refresh()->cover);
    }

    public function testUploadCoverNotAllowedForNonOwner(): void
    {
        $playlist = Playlist::factory()->create();

        $this->putAs("api/playlists/$playlist->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_user())
            ->assertForbidden();
    }

    public function testDeleteCover(): void
    {
        $playlist = Playlist::factory()->create(['cover' => 'cover.jpg']);

        $this->deleteAs("api/playlists/$playlist->id/cover", [], $playlist->user)
            ->assertNoContent();

        self::assertNull($playlist->refresh()->cover);
    }

    public function testNonOwnerCannotDeleteCover(): void
    {
        $playlist = Playlist::factory()->create(['cover' => 'cover.jpg']);

        $this->deleteAs("api/playlists/$playlist->id/cover", [], create_user())
            ->assertForbidden();

        self::assertSame('cover.jpg', $playlist->refresh()->getRawOriginal('cover'));
    }
}
