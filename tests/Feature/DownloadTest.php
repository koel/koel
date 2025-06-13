<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Song;
use App\Services\DownloadService;
use App\Values\Downloadable;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;
use function Tests\test_path;

class DownloadTest extends TestCase
{
    private MockInterface|DownloadService $downloadService;

    public function setUp(): void
    {
        parent::setUp();

        $this->downloadService = $this->mock(DownloadService::class);
    }

    #[Test]
    public function nonLoggedInUserCannotDownload(): void
    {
        $this->downloadService->shouldNotReceive('getDownloadable');

        $this->get('download/songs?songs[]=' . Song::factory()->create()->id)
            ->assertUnauthorized();
    }

    #[Test]
    public function downloadOneSong(): void
    {
        $song = Song::factory()->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadable')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($song) {
                return $retrievedSongs->count() === 1 && $retrievedSongs->first()->is($song);
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get("download/songs?songs[]={$song->id}&api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    #[Test]
    public function downloadMultipleSongs(): void
    {
        $songs = Song::factory(2)->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadable')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            // should be a zip file, but we're testing here…
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get(
            "download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}&api_token="
            . $user->createToken('Koel')->plainTextToken
        )
            ->assertOk();
    }

    #[Test]
    public function downloadAlbum(): void
    {
        $album = Album::factory()->create();
        $songs = Song::factory(3)->for($album)->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadable')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get("download/album/{$album->public_id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    #[Test]
    public function downloadArtist(): void
    {
        $artist = Artist::factory()->create();
        $songs = Song::factory(3)->for($artist)->create();
        $user = create_user();

        $this->downloadService
            ->shouldReceive('getDownloadable')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get("download/artist/{$artist->public_id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    #[Test]
    public function downloadPlaylist(): void
    {
        $user = create_user();
        $songs = Song::factory(3)->create();

        $playlist = create_playlist(owner: $user);
        $playlist->addPlayables($songs);

        $this->downloadService
            ->shouldReceive('getDownloadable')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            ->once()
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get("download/playlist/{$playlist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    #[Test]
    public function nonOwnerCannotDownloadPlaylist(): void
    {
        $playlist = create_playlist();

        $this->get("download/playlist/{$playlist->id}?api_token=" . create_user()->createToken('Koel')->plainTextToken)
            ->assertForbidden();
    }

    #[Test]
    public function downloadFavorites(): void
    {
        $user = create_user();
        $favorites = Interaction::factory(3)->for($user)->create(['liked' => true]);

        $this->downloadService
            ->shouldReceive('getDownloadable')
            ->with(Mockery::on(static function (Collection $songs) use ($favorites): bool {
                self::assertEqualsCanonicalizing($songs->modelKeys(), $favorites->pluck('song_id')->all());

                return true;
            }))
            ->once()
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get('download/favorites?api_token=' . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }
}
