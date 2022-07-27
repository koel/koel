<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;

class PlaylistSongTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        static::createSampleMediaSet();
    }

    public function testUpdatePlaylistSongs(): void
    {
        $this->doTestUpdatePlaylistSongs();
    }

    /** @deprecated  */
    public function testSyncPlaylist(): void
    {
        $this->doTestUpdatePlaylistSongs(true);
    }

    private function doTestUpdatePlaylistSongs(bool $useDeprecatedRoute = false): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([], $user);

        /** @var array<Song>|Collection $songs */
        $songs = Song::orderBy('id')->take(4)->get();
        $playlist->songs()->attach($songs->pluck('id')->all());

        /** @var Song $removedSong */
        $removedSong = $songs->pop();

        $path = $useDeprecatedRoute ? "api/playlist/$playlist->id/sync" : "api/playlist/$playlist->id/songs";

        $this->putAs($path, [
            'songs' => $songs->pluck('id')->all(),
        ], $user)->assertOk();

        // We should still see the first 3 songs, but not the removed one
        foreach ($songs as $song) {
            self::assertDatabaseHas('playlist_song', [
                'playlist_id' => $playlist->id,
                'song_id' => $song->id,
            ]);
        }

        self::assertDatabaseMissing('playlist_song', [
            'playlist_id' => $playlist->id,
            'song_id' => $removedSong->id,
        ]);
    }

    public function testGetPlaylistSongs(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
        ]);

        $songs = Song::factory(2)->create();
        $playlist->songs()->saveMany($songs);

        $this->getAs("api/playlist/$playlist->id/songs", $user)
            ->assertJson($songs->pluck('id')->all());
    }
}
