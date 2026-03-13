<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Favorite;
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

        $this->get('download/songs?songs[]=' . Song::factory()->createOne()->id)->assertUnauthorized();
    }

    #[Test]
    public function downloadOneSong(): void
    {
        $song = Song::factory()->createOne();
        $user = create_user();

        $this->downloadService
            ->expects('getDownloadable')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($song) {
                return $retrievedSongs->count() === 1 && $retrievedSongs->first()->is($song);
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get(
            "download/songs?songs[]={$song->id}&api_token=" . $user->createToken('Koel')->plainTextToken,
        )->assertOk();
    }

    #[Test]
    public function downloadMultipleSongs(): void
    {
        $songs = Song::factory()->createMany(2);
        $user = create_user();

        $this->downloadService
            ->expects('getDownloadable')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            // should be a zip file, but we're testing here…
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get(
            "download/songs?songs[]={$songs[0]->id}&songs[]={$songs[1]->id}&api_token="
            . $user->createToken('Koel')->plainTextToken,
        )->assertOk();
    }

    #[Test]
    public function downloadAlbum(): void
    {
        $album = Album::factory()->createOne();
        $songs = Song::factory()->for($album)->createMany(2);
        $user = create_user();

        $this->downloadService
            ->expects('getDownloadable')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get("download/album/{$album->id}?api_token=" . $user->createToken('Koel')->plainTextToken)->assertOk();
    }

    #[Test]
    public function downloadArtist(): void
    {
        $artist = Artist::factory()->createOne();
        $songs = Song::factory()->for($artist)->createMany(2);
        $user = create_user();

        $this->downloadService
            ->expects('getDownloadable')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get("download/artist/{$artist->id}?api_token=" . $user->createToken('Koel')->plainTextToken)->assertOk();
    }

    #[Test]
    public function downloadPlaylist(): void
    {
        $songs = Song::factory()->createMany(2);

        $playlist = create_playlist();
        $playlist->playables()->attach($songs, ['user_id' => $playlist->owner->id]);

        $this->downloadService
            ->expects('getDownloadable')
            ->with(Mockery::on(static function (Collection $retrievedSongs) use ($songs): bool {
                self::assertEqualsCanonicalizing($retrievedSongs->modelKeys(), $songs->modelKeys());

                return true;
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get(
            "download/playlist/{$playlist->id}?api_token=" . $playlist->owner->createToken('Koel')->plainTextToken,
        )->assertOk();
    }

    #[Test]
    public function nonOwnerCannotDownloadPlaylist(): void
    {
        $playlist = create_playlist();

        $this->get(
            "download/playlist/{$playlist->id}?api_token=" . create_user()->createToken('Koel')->plainTextToken,
        )->assertForbidden();
    }

    #[Test]
    public function downloadFavorites(): void
    {
        $user = create_user();

        /** @var Collection<int, Song> $songs */
        $songs = Song::factory()->for($user, 'owner')->createMany(2);

        $songs->map(static function (Song $song) use ($user): Favorite {
            return Favorite::factory()->for($user)->createOne([
                'favoriteable_id' => $song->id,
            ]);
        });

        $this->downloadService
            ->expects('getDownloadable')
            ->with(Mockery::on(static function (Collection $input) use ($songs): bool {
                self::assertEqualsCanonicalizing($input->modelKeys(), $songs->pluck('id')->all());

                return true;
            }))
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get('download/favorites?api_token=' . $user->createToken('Koel')->plainTextToken)->assertOk();
    }
}
