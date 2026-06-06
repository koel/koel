<?php

namespace Tests\Feature\Subsonic;

use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesOwnedPlaylists;
use Tests\TestCase;

use function Tests\create_user;

class GetPlaylistsTest extends TestCase
{
    use CreatesOwnedPlaylists;

    #[Test]
    public function returnsUsersPlaylists(): void
    {
        $user = create_user();
        $a = self::playlistOwnedBy($user);
        $b = self::playlistOwnedBy($user);

        $response = $this
            ->getJson("/rest/getPlaylists.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $ids = array_column($response->json('subsonic-response.playlists.playlist'), 'id');

        self::assertContains($a->id, $ids);
        self::assertContains($b->id, $ids);
    }
}
