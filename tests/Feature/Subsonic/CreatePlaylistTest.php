<?php

namespace Tests\Feature\Subsonic;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesOwnedPlaylists;
use Tests\TestCase;

use function Tests\create_user;

class CreatePlaylistTest extends TestCase
{
    use CreatesOwnedPlaylists;

    #[Test]
    public function createsNewPlaylistWithSongs(): void
    {
        $user = create_user();
        $songs = Song::factory()->count(2)->create(['owner_id' => $user->id]);
        $songParams = implode('&', array_map(static fn (Song $song) => 'songId=' . $song->id, $songs->all()));

        $this
            ->getJson(
                "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&name=Subsonic+Mix&{$songParams}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok')
            ->assertJsonPath('subsonic-response.playlist.name', 'Subsonic Mix')
            ->assertJsonPath('subsonic-response.playlist.songCount', 2);

        self::assertSame(1, $user->ownedPlaylists()->where('name', 'Subsonic Mix')->count());
    }

    #[Test]
    public function withPlaylistIdReplacesContents(): void
    {
        $user = create_user();
        $playlist = self::playlistOwnedBy($user);

        $existing = Song::factory()->count(2)->create(['owner_id' => $user->id]);
        $playlist->addPlayables($existing, $user);

        $replacements = Song::factory()->count(3)->create(['owner_id' => $user->id]);
        $songParams = implode('&', array_map(static fn (Song $song) => 'songId=' . $song->id, $replacements->all()));

        $response = $this->getJson(
            "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}"
            . "&f=json&playlistId={$playlist->id}&{$songParams}",
        )->assertOk();

        $entryIds = array_column($response->json('subsonic-response.playlist.entry'), 'id');
        self::assertEqualsCanonicalizing($replacements->modelKeys(), $entryIds);
    }

    #[Test]
    public function missingNameAndPlaylistIdReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
