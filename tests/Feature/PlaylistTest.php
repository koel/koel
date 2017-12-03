<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;

class PlaylistTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createSampleMediaSet();
    }

    /** @test */
    public function user_can_create_a_playlist()
    {
        $user = factory(User::class)->create();

        // Let's create a playlist with 3 songs
        $songs = Song::orderBy('id')->take(3)->get();

        $this->postAsUser('api/playlist', [
                'name' => 'Foo Bar',
                'songs' => $songs->pluck('id')->toArray(),
            ], $user);

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

        $this->getAsUser('api/playlist', $user)
            ->seeJson([
                'id' => $playlist->id,
                'name' => 'Foo Bar',
            ]);
    }

    /** @test */
    public function user_can_update_a_playlists_name()
    {
        $user = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->putAsUser("api/playlist/{$playlist->id}", ['name' => 'Foo Bar'], $user);

        $this->seeInDatabase('playlists', [
            'user_id' => $user->id,
            'name' => 'Foo Bar',
        ]);

        // Other users can't modify it
        $this->putAsUser("api/playlist/{$playlist->id}", ['name' => 'Foo Bar'])
            ->seeStatusCode(403);
    }

    /** @test */
    public function playlists_can_be_synced()
    {
        $user = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $songs = Song::orderBy('id')->take(4)->get();
        $playlist->songs()->attach($songs->pluck('id')->toArray());

        $removedSong = $songs->pop();

        $this->putAsUser("api/playlist/{$playlist->id}/sync", [
                'songs' => $songs->pluck('id')->toArray(),
            ])
            ->seeStatusCode(403);

        $this->putAsUser("api/playlist/{$playlist->id}/sync", [
                'songs' => $songs->pluck('id')->toArray(),
            ], $user);

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

    /** @test */
    public function user_can_delete_a_playlist()
    {
        $user = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->deleteAsUser("api/playlist/{$playlist->id}")
            ->seeStatusCode(403);

        $this->deleteAsUser("api/playlist/{$playlist->id}", [], $user)
            ->notSeeInDatabase('playlists', ['id' => $playlist->id]);
    }

    /** @test */
    public function playlist_content_can_be_retrieved()
    {
        $user = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $songs = factory(Song::class, 2)->create();
        $playlist->songs()->saveMany($songs);

        $this->getAsUser("api/playlist/{$playlist->id}/songs", $user)
            ->seeJson($songs->pluck('id')->all());
    }
}
