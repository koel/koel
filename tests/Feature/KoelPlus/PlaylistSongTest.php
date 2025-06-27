<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\CollaborativeSongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistSongTest extends PlusTestCase
{
    #[Test]
    public function getSongsInCollaborativePlaylist(): void
    {
        $playlist = create_playlist();
        $playlist->addPlayables(Song::factory()->public()->count(2)->create());

        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->getAs("api/playlists/{$playlist->id}/songs", $collaborator)
            ->assertSuccessful()
            ->assertJsonStructure([0 => CollaborativeSongResource::JSON_STRUCTURE])
            ->assertJsonCount(2);
    }

    #[Test]
    public function privateSongsDoNotShowUpInCollaborativePlaylist(): void
    {
        $playlist = create_playlist();
        $playlist->addPlayables(Song::factory()->public()->count(2)->create());

        /** @var Song $privateSong */
        $privateSong = Song::factory()->private()->create();
        $playlist->addPlayables($privateSong);

        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->getAs("api/playlists/{$playlist->id}/songs", $collaborator)
            ->assertSuccessful()
            ->assertJsonStructure([0 => CollaborativeSongResource::JSON_STRUCTURE])
            ->assertJsonCount(2)
            ->assertJsonMissing(['id' => $privateSong->id]);
    }

    #[Test]
    public function collaboratorCanAddSongs(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(2)->create();

        $this->postAs("api/playlists/{$playlist->id}/songs", ['songs' => $songs->modelKeys()], $collaborator)
            ->assertSuccessful();

        $playlist->refresh();
        $songs->each(static fn (Song $song) => self::assertTrue($playlist->playables->contains($song)));
    }

    #[Test]
    public function collaboratorCanRemoveSongs(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(2)->create();
        $playlist->addPlayables($songs);

        $this->deleteAs("api/playlists/{$playlist->id}/songs", ['songs' => $songs->modelKeys()], $collaborator)
            ->assertSuccessful();

        $playlist->refresh();
        $songs->each(static fn (Song $song) => self::assertFalse($playlist->playables->contains($song)));
    }
}
