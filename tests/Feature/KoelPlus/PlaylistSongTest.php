<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\CollaborativeSongResource;
use App\Models\Playlist;
use App\Models\Song;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistSongTest extends PlusTestCase
{
    public function testGetSongsInCollaborativePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addSongs(Song::factory()->public()->count(3)->create());

        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->getAs("api/playlists/$playlist->id/songs", $collaborator)
            ->assertSuccessful()
            ->assertJsonStructure(['*' => CollaborativeSongResource::JSON_STRUCTURE])
            ->assertJsonCount(3);
    }

    public function testPrivateSongsDoNotShowUpInCollaborativePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addSongs(Song::factory()->public()->count(3)->create());

        /** @var Song $privateSong */
        $privateSong = Song::factory()->private()->create();
        $playlist->addSongs($privateSong);

        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->getAs("api/playlists/$playlist->id/songs", $collaborator)
            ->assertSuccessful()
            ->assertJsonStructure(['*' => CollaborativeSongResource::JSON_STRUCTURE])
            ->assertJsonCount(3)
            ->assertJsonMissing(['id' => $privateSong->id]);
    }

    public function testCollaboratorCanAddSongs(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(3)->create();

        $this->postAs("api/playlists/$playlist->id/songs", ['songs' => $songs->pluck('id')->all()], $collaborator)
            ->assertSuccessful();

        $playlist->refresh();
        $songs->each(static fn (Song $song) => self::assertTrue($playlist->songs->contains($song)));
    }

    public function testCollaboratorCanRemoveSongs(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(3)->create();
        $playlist->addSongs($songs);

        $this->deleteAs("api/playlists/$playlist->id/songs", ['songs' => $songs->pluck('id')->all()], $collaborator)
            ->assertSuccessful();

        $playlist->refresh();
        $songs->each(static fn (Song $song) => self::assertFalse($playlist->songs->contains($song)));
    }
}
