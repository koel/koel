<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Song;
use App\Services\DownloadService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class DownloadTest extends TestCase
{
    private MockInterface|DownloadService $downloadService;

    public function setUp(): void
    {
        parent::setUp();

        $this->downloadService = self::mock(DownloadService::class);
    }

    public function testNonLoggedInUserCannotDownload(): void
    {
        $this->downloadService->shouldNotReceive('getDownloadablePath');

        $this->get('download/songs?songs[]=' . Song::factory()->create()->id)
            ->assertUnauthorized();
    }

    public function testDownloadOneSong(): void
    {
        $song = Song::factory()->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadablePath')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($song) {
                return $retrievedSongs->count() === 1 && $retrievedSongs->first()->id === $song->id;
            }))
            ->andReturn(test_path('songs/blank.mp3'));

        $this->get("download/songs?songs[]={$song->id}&api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadMultipleSongs(): void
    {
        $songs = Song::factory(2)->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadablePath')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->pluck('id')->all(), $songs->pluck('id')->all());

                return true;
            }))
            ->andReturn(test_path('songs/blank.mp3')); // should be a zip file, but we're testing here…

        $this->get(
            "download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}&api_token="
            . $user->createToken('Koel')->plainTextToken
        )
            ->assertOk();
    }

    public function testDownloadAlbum(): void
    {
        $album = Album::factory()->create();
        $songs = Song::factory(3)->for($album)->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadablePath')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->pluck('id')->all(), $songs->pluck('id')->all());

                return true;
            }))
            ->andReturn(test_path('songs/blank.mp3'));

        $this->get("download/album/{$album->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadArtist(): void
    {
        $artist = Artist::factory()->create();
        $songs = Song::factory(3)->for($artist)->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadablePath')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->pluck('id')->all(), $songs->pluck('id')->all());

                return true;
            }))
            ->andReturn(test_path('songs/blank.mp3'));

        $this->get("download/artist/{$artist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadPlaylist(): void
    {
        $user = create_user();
        $songs = Song::factory(3)->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();
        $playlist->addSongs($songs);

        $this->downloadService
            ->shouldReceive('getDownloadablePath')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->pluck('id')->all(), $songs->pluck('id')->all());

                return true;
            }))
            ->once()
            ->andReturn(test_path('songs/blank.mp3'));

        $this->get("download/playlist/{$playlist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testNonOwnerCannotDownloadPlaylist(): void
    {
        $playlist = Playlist::factory()->create();

        $this->get("download/playlist/{$playlist->id}?api_token=" . create_user()->createToken('Koel')->plainTextToken)
            ->assertForbidden();
    }

    public function testDownloadFavorites(): void
    {
        $user = create_user();
        $favorites = Interaction::factory(3)->for($user)->create(['liked' => true]);

        $this->downloadService
            ->shouldReceive('getDownloadablePath')
            ->with(Mockery::on(static function (Collection $songs) use ($favorites): bool {
                self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $favorites->pluck('song_id')->all());

                return true;
            }))
            ->once()
            ->andReturn(test_path('songs/blank.mp3'));

        $this->get('download/favorites?api_token=' . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }
}
