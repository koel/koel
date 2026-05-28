<?php

namespace Tests\Feature\Subsonic;

use App\Models\Playlist;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesOwnedPlaylists;
use Tests\TestCase;

use function Tests\create_user;

class DeletePlaylistTest extends TestCase
{
    use CreatesOwnedPlaylists;

    #[Test]
    public function deletesPlaylist(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user);

        $this
            ->getJson("/rest/deletePlaylist.view?apiKey={$user->subsonic_api_key}&f=json&id={$playlist->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertNull(Playlist::query()->find($playlist->id));
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/deletePlaylist.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
