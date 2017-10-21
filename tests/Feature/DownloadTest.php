<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Download;

class DownloadTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createSampleMediaSet();
    }

    /** @test */
    public function a_single_song_can_be_downloaded()
    {
        $song = Song::first();
        Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/songs?songs[]={$song->id}")
            ->seeStatusCode(200);
    }

    /** @test */
    public function multiple_songs_can_be_downloaded()
    {
        $songs = Song::take(2)->get();
        Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3'); // should be a zip file, but we're testing here…

        $this->getAsUser("api/download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}")
            ->seeStatusCode(200);
    }

    /** @test */
    public function a_whole_album_can_be_downloaded()
    {
        $album = Album::first();

        Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/album/{$album->id}")
            ->seeStatusCode(200);
    }

    /** @test */
    public function a_whole_artists_biography_can_be_downloaded()
    {
        $artist = Artist::first();

        Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/artist/{$artist->id}")
            ->seeStatusCode(200);
    }

    /** @test */
    public function a_whole_playlist_can_be_downloaded()
    {
        $user = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->getAsUser("api/download/playlist/{$playlist->id}")
            ->seeStatusCode(403);

        Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/playlist/{$playlist->id}", $user)
            ->seeStatusCode(200);
    }

    /** @test */
    public function all_favorite_songs_can_be_downloaded()
    {
        Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser('api/download/favorites')
            ->seeStatusCode(200);
    }
}
