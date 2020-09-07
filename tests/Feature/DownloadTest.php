<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\InteractionRepository;
use App\Services\DownloadService;
use Exception;
use Illuminate\Http\Response;
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
    public function setUp(): void
    {
        parent::setUp();

        static::createSampleMediaSet();
        $this->downloadService = static::mockIocDependency(DownloadService::class);
    }

    public function testNonLoggedInUserCannotDownload(): void
    {
        $song = Song::first();

        $this->downloadService
            ->shouldReceive('from')
            ->never();

        $this->get("download/songs?songs[]={$song->id}")
            ->assertRedirect('/');
    }

    public function testDownloadOneSong(): void
    {
        $song = Song::first();

        /** @var User $user */
        $user = factory(User::class)->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($song) {
                return $retrievedSongs->count() === 1 && $retrievedSongs->first()->id === $song->id;
            }))
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->get("download/songs?songs[]={$song->id}&api_token=".$user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadMultipleSongs(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $songs = Song::take(2)->orderBy('id')->get();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                $retrievedIds = $retrievedSongs->pluck('id')->toArray();
                $requestedIds = $songs->pluck('id')->toArray();

                return $requestedIds[0] === $retrievedIds[0] && $requestedIds[1] === $retrievedIds[1];
            }))
            ->andReturn($this->mediaPath.'/blank.mp3'); // should be a zip file, but we're testing here…

        $this->get(
            "download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}&api_token="
            .$user->createToken('Koel')->plainTextToken
        )
            ->assertOk();
    }

    public function testDownloadAlbum(): void
    {
        $album = Album::first();

        /** @var User $user */
        $user = factory(User::class)->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Album $retrievedAlbum) use ($album): bool {
                return $retrievedAlbum->id === $album->id;
            }))
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->get("download/album/{$album->id}?api_token=".$user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadArtist(): void
    {
        $artist = Artist::first();

        /** @var User $user */
        $user = factory(User::class)->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Artist $retrievedArtist) use ($artist): bool {
                return $retrievedArtist->id === $artist->id;
            }))
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->get("download/artist/{$artist->id}?api_token=".$user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadPlaylist(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        /** @var Playlist $playlist */
        $playlist = factory(Playlist::class)->create([
            'user_id' => $user->id,
        ]);

        $this->downloadService
            ->shouldReceive('from')
            ->with(Mockery::on(static function (Playlist $retrievedPlaylist) use ($playlist): bool {
                return $retrievedPlaylist->id === $playlist->id;
            }))
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->get("download/playlist/{$playlist->id}?api_token=".$user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testNonOwnerCannotDownloadPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = factory(Playlist::class)->create();

        /** @var User $user */
        $user = factory(User::class)->create();

        $this->get("download/playlist/{$playlist->id}?api_token=".$user->createToken('Koel')->plainTextToken)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testDownloadFavorites(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $favorites = Collection::make();

        static::mockIocDependency(InteractionRepository::class)
            ->shouldReceive('getUserFavorites')
            ->once()
            ->with(Mockery::on(static function (User $retrievedUser) use ($user): bool {
                return $retrievedUser->id === $user->id;
            }))
            ->andReturn($favorites);

        $this->downloadService
            ->shouldReceive('from')
            ->with($favorites)
            ->once()
            ->andReturn($this->mediaPath.'/blank.mp3');

        $this->get('download/favorites?api_token='.$user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }
}
