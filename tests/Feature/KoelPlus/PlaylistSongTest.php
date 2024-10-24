<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\CollaborativeSongResource;
use App\Models\Playlist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistSongTest extends PlusTestCase
{
    #[Test]
    public function getSongsInCollaborativePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addPlayables(Song::factory()->public()->count(3)->create());

        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->getAs("api/playlists/$playlist->id/songs", $collaborator)
            ->assertSuccessful()
            ->assertJsonStructure(['*' => CollaborativeSongResource::JSON_STRUCTURE])
            ->assertJsonCount(3);
    }

    #[Test]
    public function privateSongsDoNotShowUpInCollaborativePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addPlayables(Song::factory()->public()->count(3)->create());

        /** @var Song $privateSong */
        $privateSong = Song::factory()->private()->create();
        $playlist->addPlayables($privateSong);

        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->getAs("api/playlists/$playlist->id/songs", $collaborator)
            ->assertSuccessful()
            ->assertJsonStructure(['*' => CollaborativeSongResource::JSON_STRUCTURE])
            ->assertJsonCount(3)
            ->assertJsonMissing(['id' => $privateSong->id]);
    }

    #[Test]
    public function collaboratorCanAddSongs(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(3)->create();

        $this->postAs("api/playlists/$playlist->id/songs", ['songs' => $songs->pluck('id')->all()], $collaborator)
            ->assertSuccessful();

        $playlist->refresh();
        $songs->each(static fn (Song $song) => self::assertTrue($playlist->playables->contains($song)));
    }

    #[Test]
    public function collaboratorCanRemoveSongs(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);
        $songs = Song::factory()->for($collaborator, 'owner')->count(3)->create();
        $playlist->addPlayables($songs);

        $this->deleteAs("api/playlists/$playlist->id/songs", ['songs' => $songs->pluck('id')->all()], $collaborator)
            ->assertSuccessful();

        $playlist->refresh();
        $songs->each(static fn (Song $song) => self::assertFalse($playlist->playables->contains($song)));
    }
}
