<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

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

        $this->getAs("api/playlists/$playlist->id/songs", $playlist->user)
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }

    public function testNonOwnerCannotAccessPlaylist(): void
    {
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();
        $playlist->songs()->attach(Song::factory(5)->create());

        $this->getAs('api/playlists/' . $playlist->id . '/songs')
            ->assertForbidden();
    }

    public function testAddSongsToPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        /** @var Collection|array<array-key, Song> $songs */
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

        /** @var Collection|array<array-key, Song> $toBeRemovedSongs */
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
            ->assertForbidden();

        $this->deleteAs('api/playlists/' . $playlist->id . '/songs', ['songs' => [$song->id]])
            ->assertForbidden();
    }

    public function testSmartPlaylistContentCannotBeModified(): void
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

        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory(2)->create();
        $songIds = $songs->map(static fn (Song $song) => $song->id)->all();

        $this->postAs('api/playlists/' . $playlist->id . '/songs', ['songs' => $songIds], $playlist->user)
            ->assertForbidden();

        $this->deleteAs('api/playlists/' . $playlist->id . '/songs', ['songs' => $songIds], $playlist->user)
            ->assertForbidden();
    }
}
