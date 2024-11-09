<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Playlist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlaylistSongTest extends TestCase
{
    #[Test]
    public function getNormalPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addPlayables(Song::factory(5)->create());

        $this->getAs("api/playlists/{$playlist->id}/songs", $playlist->user)
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function getSmartPlaylist(): void
    {
        Song::factory()->create(['title' => 'A foo song']);

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'rules' => [
                [
                    'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                    'rules' => [
                        [
                            'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                            'model' => 'title',
                            'operator' => 'contains',
                            'value' => ['foo'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->getAs("api/playlists/{$playlist->id}/songs", $playlist->user)
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function nonOwnerCannotAccessPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for(create_user())->create();
        $playlist->addPlayables(Song::factory(5)->create());

        $this->getAs("api/playlists/{$playlist->id}/songs")
            ->assertForbidden();
    }

    #[Test]
    public function addSongsToPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $songs = Song::factory(2)->create();

        $this->postAs("api/playlists/{$playlist->id}/songs", ['songs' => $songs->modelKeys()], $playlist->user)
            ->assertSuccessful();

        self::assertEqualsCanonicalizing($songs->modelKeys(), $playlist->playables->modelKeys());
    }

    #[Test]
    public function removeSongsFromPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $toRemainSongs = Song::factory(5)->create();

        $toBeRemovedSongs = Song::factory(2)->create();

        $playlist->addPlayables($toRemainSongs->merge($toBeRemovedSongs));

        self::assertCount(7, $playlist->playables);

        $this->deleteAs(
            "api/playlists/{$playlist->id}/songs",
            ['songs' => $toBeRemovedSongs->modelKeys()],
            $playlist->user
        )
            ->assertNoContent();

        $playlist->refresh();

        self::assertEqualsCanonicalizing($toRemainSongs->modelKeys(), $playlist->playables->modelKeys());
    }

    #[Test]
    public function nonOwnerCannotModifyPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for(create_user())->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->postAs("api/playlists/{$playlist->id}/songs", ['songs' => [$song->id]])
            ->assertForbidden();

        $this->deleteAs("api/playlists/{$playlist->id}/songs", ['songs' => [$song->id]])
            ->assertForbidden();
    }

    #[Test]
    public function smartPlaylistContentCannotBeModified(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'rules' => [
                [
                    'id' => '45368b8f-fec8-4b72-b826-6b295af0da65',
                    'rules' => [
                        [
                            'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                            'model' => 'title',
                            'operator' => 'contains',
                            'value' => ['foo'],
                        ],
                    ],
                ],
            ],
        ]);

        $songs = Song::factory(2)->create()->modelKeys();

        $this->postAs("api/playlists/{$playlist->id}/songs", ['songs' => $songs], $playlist->user)
            ->assertForbidden();

        $this->deleteAs("api/playlists/{$playlist->id}/songs", ['songs' => $songs], $playlist->user)
            ->assertForbidden();
    }
}
