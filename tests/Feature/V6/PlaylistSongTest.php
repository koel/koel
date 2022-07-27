<?php

namespace Tests\Feature\V6;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Http\Response;

class PlaylistSongTest extends TestCase
{
    public function testGetNormalPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->songs()->attach(Song::factory(5)->create());

        $this->getAs('api/playlists/' . $playlist->id . '/songs', $playlist->user)
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }

    public function testGetSmartPlaylist(): void
    {
        Song::factory()->create(['title' => 'A foo song']);

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'rules' => [
                [
                    'id' => 1658843809274,
                    'rules' => [
                        [
                            'id' => 1658843809274,
                            'model' => 'title',
                            'operator' => 'contains',
                            'value' => ['foo'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->getAs('api/playlists/' . $playlist->id . '/songs', $playlist->user)
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }

    public function testNonOwnerCannotAccessPlaylist(): void
    {
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();
        $playlist->songs()->attach(Song::factory(5)->create());

        $this->getAs('api/playlists/' . $playlist->id . '/songs')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAddSongsToPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $songs = Song::factory(2)->create();

        $this->postAs('api/playlists/' . $playlist->id . '/songs', [
            'songs' => $songs->map(static fn (Song $song) => $song->id)->all(),
        ], $playlist->user)
            ->assertNoContent();

        self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $playlist->songs->pluck('id')->all());
    }

    public function testRemoveSongsFromPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $toRemainSongs = Song::factory(5)->create();
        $toBeRemovedSongs = Song::factory(2)->create();
        $playlist->songs()->attach($toRemainSongs->merge($toBeRemovedSongs));

        self::assertCount(7, $playlist->songs);

        $this->deleteAs('api/playlists/' . $playlist->id . '/songs', [
            'songs' => $toBeRemovedSongs->map(static fn (Song $song) => $song->id)->all(),
        ], $playlist->user)
            ->assertNoContent();

        $playlist->refresh();

        self::assertEqualsCanonicalizing($toRemainSongs->pluck('id')->all(), $playlist->songs->pluck('id')->all());
    }

    public function testNonOwnerCannotModifyPlaylist(): void
    {
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->postAs('api/playlists/' . $playlist->id . '/songs', ['songs' => [$song->id]])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->deleteAs('api/playlists/' . $playlist->id . '/songs', ['songs' => [$song->id]])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testSmartPlaylistContentCannotBeModified(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'rules' => [
                [
                    'id' => 1658843809274,
                    'rules' => [
                        [
                            'id' => 1658843809274,
                            'model' => 'title',
                            'operator' => 'contains',
                            'value' => ['foo'],
                        ],
                    ],
                ],
            ],
        ]);

        $songs = Song::factory(2)->create()->map(static fn (Song $song) => $song->id)->all();

        $this->postAs('api/playlists/' . $playlist->id . '/songs', ['songs' => $songs], $playlist->user)
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->deleteAs('api/playlists/' . $playlist->id . '/songs', ['songs' => $songs], $playlist->user)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
