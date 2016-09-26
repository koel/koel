<?php

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DownloadTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->createSampleMediaSet();
    }

    public function testOneSong()
    {
        $song = Song::first();
        $mocked = Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/songs?songs[]={$song->id}")
            ->seeStatusCode(200);
    }

    public function testMultipleSongs()
    {
        $songs = Song::take(2)->get();
        $mocked = Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3'); // should be a zip file, but we're testing here…

        $this->getAsUser("api/download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}")
            ->seeStatusCode(200);
    }

    public function testAlbum()
    {
        $album = Album::first();

        $mocked = Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/album/{$album->id}")
            ->seeStatusCode(200);
    }

    public function testArtist()
    {
        $artist = Artist::first();

        $mocked = Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/artist/{$artist->id}")
            ->seeStatusCode(200);
    }

    public function testPlaylist()
    {
        $user = factory(User::class)->create();

        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->getAsUser("api/download/playlist/{$playlist->id}")
            ->seeStatusCode(403);

        $mocked = Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/playlist/{$playlist->id}", $user)
            ->seeStatusCode(200);
    }

    public function testFavorites()
    {
        $mocked = Download::shouldReceive('from')
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser('api/download/favorites')
            ->seeStatusCode(200);
    }
}
