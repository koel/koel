<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Playlist;
use App\Models\Song;
use Tests\Feature\SongTest as CommunitySongTest;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistSongTest extends PlusTestCase
{
    private array $songJsonStructure;

    public function setUp(): void
    {
        parent::setUp();

        $this->songJsonStructure = CommunitySongTest::JSON_STRUCTURE + [
                'collaboration' => [
                    'user' => [
                        'avatar',
                        'name',
                    ],
                    'added_at',
                    'fmt_added_at',
                ],
            ];
    }

    public function testGetSongsInCollaborativePlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addSongs(Song::factory()->public()->count(3)->create());

        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->getAs("api/playlists/$playlist->id/songs", $collaborator)
            ->assertSuccessful()
            ->assertJsonStructure(['*' => $this->songJsonStructure])
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
            ->assertJsonStructure(['*' => $this->songJsonStructure])
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

        self::assertArraySubset($songs->pluck('id')->all(), $playlist->songs->pluck('id')->all());
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

        self::assertEmpty($playlist->refresh()->songs);
    }
}
