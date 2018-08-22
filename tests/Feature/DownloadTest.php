<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Services\DownloadService;
use App\Services\InteractionService;
use Exception;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;

class DownloadTest extends TestCase
{
    /**
     * @var MockInterface|DownloadService
     */
    private $downloadService;

    /**
     * @throws Exception
     */
    public function setUp()
    {
        parent::setUp();
        $this->createSampleMediaSet();
        $this->downloadService = $this->mockIocDependency(DownloadService::class);
    }

    public function testDownloadOneSong()
    {
        $song = Song::first();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($song) {
                return $retrievedSongs->count() === 1 && $retrievedSongs->first()->id === $song->id;
            }))
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/songs?songs[]={$song->id}")
            ->assertResponseOk();
    }

    public function testDownloadMultipleSongs()
    {
        $songs = Song::take(2)->orderBy('id')->get();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs) {
                $retrievedIds = $retrievedSongs->pluck('id')->toArray();
                $requestedIds = $songs->pluck('id')->toArray();

                return $requestedIds[0] === $retrievedIds[0] && $requestedIds[1] === $retrievedIds[1];
            }))
            ->andReturn($this->mediaPath.'/blank.mp3'); // should be a zip file, but we're testing here…

        $this->getAsUser("api/download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}")
            ->assertResponseOk();
    }

    public function testDownloadAlbum()
    {
        $album = Album::first();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Album $retrievedAlbum) use ($album) {
                return $retrievedAlbum->id === $album->id;
            }))
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/album/{$album->id}")
            ->assertResponseOk();
    }

    public function testDownloadArtist()
    {
        $artist = Artist::first();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Artist $retrievedArtist) use ($artist) {
                return $retrievedArtist->id === $artist->id;
            }))
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/artist/{$artist->id}")
            ->assertResponseOk();
    }

    public function testDownloadPlaylist()
    {
        $user = factory(User::class)->create();

        /** @var Playlist $playlist */
        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->getAsUser("api/download/playlist/{$playlist->id}")
            ->assertResponseStatus(403);

        $this->downloadService
            ->shouldReceive('from')
            ->with(Mockery::on(static function (Playlist $retrievedPlaylist) use ($playlist) {
                return $retrievedPlaylist->id === $playlist->id;
            }))
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser("api/download/playlist/{$playlist->id}", $user)
            ->assertResponseOk();
    }

    public function testDownloadFavorites()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $favorites = Collection::make();

        $this->mockIocDependency(InteractionService::class)
            ->shouldReceive('getUserFavorites')
            ->once()
            ->with(Mockery::on(static function (User $retrievedUser) use ($user) {
                return $retrievedUser->id === $user->id;
            }))
            ->andReturn($favorites);

        $this->downloadService
            ->shouldReceive('from')
            ->with($favorites)
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->getAsUser('api/download/favorites', $user)
            ->seeStatusCode(200);
    }
}
