<?php

namespace Tests\Feature\Subsonic;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesOwnedPlaylists;
use Tests\TestCase;

use function Tests\create_user;

class GetPlaylistTest extends TestCase
{
    use CreatesOwnedPlaylists;

    #[Test]
    public function returnsPlaylistWithSongs(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user);

        $songs = Song::factory()->count(3)->create(['owner_id' => $user->id]);
        $playlist->addPlayables($songs, $user);

        $response = $this
            ->getJson("/rest/getPlaylist.view?apiKey={$user->subsonic_api_key}&f=json&id={$playlist->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.playlist.id', $playlist->id)
            ->assertJsonPath('subsonic-response.playlist.songCount', 3);

        $entryIds = array_column($response->json('subsonic-response.playlist.entry'), 'id');
        self::assertEqualsCanonicalizing($songs->modelKeys(), $entryIds);
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getPlaylist.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
