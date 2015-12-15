<?php

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class PlaylistTest extends TestCase
{
    use WithoutMiddleware, DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();

        $this->createSampleMediaSet();
    }

    public function testCreatePlaylist()
    {
        $user = factory(User::class)->create();

        // Let's create a playlist with 3 songs
        $songs = Song::orderBy('id')->take(3)->get();
        $songIds = array_pluck($songs->toArray(), 'id');

        $this->actingAs($user)
            ->post('api/playlist', [
                'name' => 'Foo Bar',
                'songs' => $songIds,
            ]);

        $this->seeInDatabase('playlists', [
            'user_id' => $user->id,
            'name' => 'Foo Bar',
        ]);

        $playlist = Playlist::orderBy('id', 'desc')->first();

        foreach ($songs as $song) {
            $this->seeInDatabase('playlist_song', [
                'playlist_id' => $playlist->id,
                'song_id' => $song->id,
            ]);
        }
    }

    public function testUpdatePlaylistName()
    {
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user2)
            ->put("api/playlist/{$playlist->id}", ['name' => 'Foo Bar'])
            ->seeStatusCode(403);

        $this->actingAs($user)
            ->put("api/playlist/{$playlist->id}", ['name' => 'Foo Bar']);

        $this->seeInDatabase('playlists', [
            'user_id' => $user->id,
            'name' => 'Foo Bar',
        ]);

        // Other users can't modify it
        $this->actingAs(factory(User::class)->create())
            ->put("api/playlist/{$playlist->id}", ['name' => 'Foo Bar'])
            ->seeStatusCode(403);
    }

    public function testSyncPlaylist()
    {
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $songs = Song::orderBy('id')->take(4)->get();
        $playlist->songs()->attach(array_pluck($songs->toArray(), 'id'));

        $removedSong = $songs->pop();

        $this->actingAs($user2)
            ->put("api/playlist/{$playlist->id}/sync", [
                'songs' => array_pluck($songs->toArray(), 'id'),
            ])
            ->seeStatusCode(403);

        $this->actingAs($user)
            ->put("api/playlist/{$playlist->id}/sync", [
                'songs' => array_pluck($songs->toArray(), 'id'),
            ]);

        // We should still see the first 3 songs, but not the removed one
        foreach ($songs as $song) {
            $this->seeInDatabase('playlist_song', [
                'playlist_id' => $playlist->id,
                'song_id' => $song->id,
            ]);
        }

        $this->notSeeInDatabase('playlist_song', [
            'playlist_id' => $playlist->id,
            'song_id' => $removedSong->id,
        ]);
    }

    public function testDeletePlaylist()
    {
        $user = factory(User::class)->create();
        $user2 = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user2)
            ->delete("api/playlist/{$playlist->id}")
            ->seeStatusCode(403);

        $this->actingAs($user)
            ->delete("api/playlist/{$playlist->id}")
            ->notSeeInDatabase('playlists', ['id' => $playlist->id]);
    }
}
