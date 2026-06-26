<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class UpdatePlaylistTest extends PlusTestCase
{
    #[Test]
    public function collaboratorCanAddSongs(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(2)->create();
        $songParams = implode('&', array_map(static fn (Song $song) => 'songIdToAdd=' . $song->id, $songs->all()));

        $this
            ->getJson(
                "/rest/updatePlaylist.view?apiKey={$collaborator->subsonic_api_key}"
                . "&f=json&playlistId={$playlist->id}&{$songParams}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertEqualsCanonicalizing($songs->modelKeys(), $playlist->refresh()->playables->modelKeys());
    }

    #[Test]
    public function collaboratorCanRemoveSongs(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(3)->create();
        $playlist->addPlayables($songs, $collaborator);

        $this
            ->getJson(
                "/rest/updatePlaylist.view?apiKey={$collaborator->subsonic_api_key}"
                . "&f=json&playlistId={$playlist->id}&songIndexToRemove=1",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $remaining = $playlist->refresh()->playables->modelKeys();
        self::assertCount(2, $remaining);
        self::assertNotContains($songs[1]->id, $remaining);
    }

    #[Test]
    public function collaboratorCannotRenamePlaylist(): void
    {
        $playlist = create_playlist(['name' => 'Original']);
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this
            ->getJson(
                "/rest/updatePlaylist.view?apiKey={$collaborator->subsonic_api_key}"
                . "&f=json&playlistId={$playlist->id}&name=Hijacked",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 50);

        self::assertSame('Original', $playlist->refresh()->name);
    }
}
