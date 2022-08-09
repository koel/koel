<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\InteractionRepository;
use App\Services\DownloadService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;

class DownloadTest extends TestCase
{
    private MockInterface|DownloadService $downloadService;

    public function setUp(): void
    {
        parent::setUp();

        static::createSampleMediaSet();
        $this->downloadService = self::mock(DownloadService::class);
    }

    public function testNonLoggedInUserCannotDownload(): void
    {
        /** @var Song $song */
        $song = Song::query()->first();

        $this->downloadService
            ->shouldReceive('from')
            ->never();

        $this->get("download/songs?songs[]=$song->id")
            ->assertRedirect('/');
    }

    public function testDownloadOneSong(): void
    {
        /** @var Song $song */
        $song = Song::query()->first();

        /** @var User $user */
        $user = User::factory()->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($song) {
                return $retrievedSongs->count() === 1 && $retrievedSongs->first()->id === $song->id;
            }))
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get("download/songs?songs[]={$song->id}&api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadMultipleSongs(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var array<Song>|Collection $songs */
        $songs = Song::query()->take(2)->orderBy('id')->get();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                $retrievedIds = $retrievedSongs->pluck('id')->toArray();
                $requestedIds = $songs->pluck('id')->toArray();

                return $requestedIds[0] === $retrievedIds[0] && $requestedIds[1] === $retrievedIds[1];
            }))
            ->andReturn($this->mediaPath . '/blank.mp3'); // should be a zip file, but we're testing here…

        $this->get(
            "download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}&api_token="
            . $user->createToken('Koel')->plainTextToken
        )
            ->assertOk();
    }

    public function testDownloadAlbum(): void
    {
        /** @var Album $album */
        $album = Album::query()->first();

        /** @var User $user */
        $user = User::factory()->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Album $retrievedAlbum) use ($album): bool {
                return $retrievedAlbum->id === $album->id;
            }))
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get("download/album/{$album->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadArtist(): void
    {
        /** @var Artist $artist */
        $artist = Artist::query()->first();

        /** @var User $user */
        $user = User::factory()->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Artist $retrievedArtist) use ($artist): bool {
                return $retrievedArtist->id === $artist->id;
            }))
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get("download/artist/{$artist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->downloadService
            ->shouldReceive('from')
            ->with(Mockery::on(static function (Playlist $retrievedPlaylist) use ($playlist): bool {
                return $retrievedPlaylist->id === $playlist->id;
            }))
            ->once()
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get("download/playlist/{$playlist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testNonOwnerCannotDownloadPlaylist(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->get("download/playlist/{$playlist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertForbidden();
    }

    public function testDownloadFavorites(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $favorites = Collection::make();

        self::mock(InteractionRepository::class)
            ->shouldReceive('getUserFavorites')
            ->once()
            ->with(Mockery::on(static fn (User $retrievedUser) => $retrievedUser->is($user)))
            ->andReturn($favorites);

        $this->downloadService
            ->shouldReceive('from')
            ->with($favorites)
            ->once()
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get('download/favorites?api_token=' . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }
}
