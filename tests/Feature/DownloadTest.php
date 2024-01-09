<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Services\DownloadService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

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
            ->assertUnauthorized();
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
                $retrievedIds = $retrievedSongs->pluck('id')->all();
                $requestedIds = $songs->pluck('id')->all();
                self::assertEqualsCanonicalizing($requestedIds, $retrievedIds);

                return true;
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

        $songs = Song::factory(3)->create(['album_id' => $album->id]);

        /** @var User $user */
        $user = User::factory()->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->pluck('id')->all(), $songs->pluck('id')->all());

                return true;
            }))
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get("download/album/{$album->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadArtist(): void
    {
        /** @var Artist $artist */
        $artist = Artist::query()->first();

        $songs = Song::factory(3)->create(['artist_id' => $artist->id]);

        /** @var User $user */
        $user = User::factory()->create();

        $this->downloadService
            ->shouldReceive('from')
            ->once()
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->pluck('id')->all(), $songs->pluck('id')->all());

                return true;
            }))
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get("download/artist/{$artist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }

    public function testDownloadPlaylist(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $songs = Song::factory(3)->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();

        $playlist->songs()->attach($songs);

        $this->downloadService
            ->shouldReceive('from')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->pluck('id')->all(), $songs->pluck('id')->all());

                return true;
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

        $favorites = Interaction::factory(3)->for($user)->create(['liked' => true]);

        $this->downloadService
            ->shouldReceive('from')
            ->with(Mockery::on(static function (Collection $songs) use ($favorites): bool {
                self::assertEqualsCanonicalizing($songs->pluck('id')->all(), $favorites->pluck('song_id')->all());

                return true;
            }))
            ->once()
            ->andReturn($this->mediaPath . '/blank.mp3');

        $this->get('download/favorites?api_token=' . $user->createToken('Koel')->plainTextToken)
            ->assertOk();
    }
}
