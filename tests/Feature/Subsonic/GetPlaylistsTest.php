<?php

namespace Tests\Feature\Subsonic;

use App\Models\Song;
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
        self::assertSame($user->email, $response->json('subsonic-response.playlists.playlist.0.owner'));
    }

    #[Test]
    public function countTheSongsOfSmartPlaylists(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user, smart: true);
        Song::factory()->createMany([
            ['artist_name' => 'foo', 'length' => 100],
            ['artist_name' => 'foo', 'length' => 50],
        ]);

        $response = $this->getJson("/rest/getPlaylists.view?apiKey={$user->subsonic_api_key}&f=json")->assertOk();

        $listed = collect($response->json('subsonic-response.playlists.playlist'))->firstWhere('id', $playlist->id);

        self::assertSame(2, $listed['songCount']);
        self::assertSame(150, $listed['duration']);
    }
}
