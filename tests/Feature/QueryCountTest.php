<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsFlatQueryCount;
use Tests\TestCase;

use function Tests\create_user;

class QueryCountTest extends TestCase
{
    use AssertsFlatQueryCount;

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
    }

    protected function createPlayedSongs(int $count): void
    {
        Song::factory()
            ->createMany($count)
            ->each(fn (Song $song) => Interaction::factory()->for($this->user)->for($song)->createOne());
    }

    protected function createPlaylists(int $count): void
    {
        Playlist::factory()
            ->createMany($count)
            ->each(fn (Playlist $playlist) => $playlist->users()->sync([$this->user->id => ['role' => 'owner']]));
    }

    #[Test]
    public function listSongs(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlayedSongs(...),
            fn () => $this->getAs('api/songs?sort=title&order=asc', $this->user),
        );
    }

    #[Test]
    public function listAlbums(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlayedSongs(...),
            fn () => $this->getAs('api/albums?sort=name&order=asc', $this->user),
        );
    }

    #[Test]
    public function listArtists(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlayedSongs(...),
            fn () => $this->getAs('api/artists?sort=name&order=asc', $this->user),
        );
    }

    #[Test]
    public function listGenres(): void
    {
        $this->assertQueryCountStaysFlat(
            static fn (int $count) => Song::factory()
                ->createMany($count)
                ->each(static fn (Song $song) => $song->syncGenres(fake()->unique()->word())),
            fn () => $this->getAs('api/genres', $this->user),
        );
    }

    #[Test]
    public function listPlaylists(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlaylists(...),
            fn () => $this->getAs('api/playlists', $this->user),
        );
    }

    #[Test]
    public function listPodcasts(): void
    {
        $this->assertQueryCountStaysFlat(
            fn (int $count) => Podcast::factory()
                ->createMany($count)
                ->each(fn (Podcast $podcast) => $podcast->subscribers()->attach($this->user)),
            fn () => $this->getAs('api/podcasts', $this->user),
        );
    }

    #[Test]
    public function listRadioStations(): void
    {
        $this->assertQueryCountStaysFlat(
            fn (int $count) => RadioStation::factory()->for($this->user)->createMany($count),
            fn () => $this->getAs('api/radio/stations', $this->user),
        );
    }

    #[Test]
    public function showTheOverview(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlayedSongs(...),
            fn () => $this->getAs('api/overview', $this->user),
        );
    }

    #[Test]
    public function fetchTheQueue(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlayedSongs(...),
            fn () => $this->getAs('api/queue/fetch?order=asc&sort=title&limit=50', $this->user),
        );
    }

    #[Test]
    public function listAnAlbumsSongs(): void
    {
        $album = Album::factory()->createOne();

        $this->assertQueryCountStaysFlat(
            static fn (int $count) => Song::factory()->for($album)->createMany($count),
            fn () => $this->getAs("api/albums/{$album->id}/songs", $this->user),
        );
    }

    #[Test]
    public function listAnArtistsSongs(): void
    {
        $artist = Artist::factory()->createOne();

        $this->assertQueryCountStaysFlat(
            static fn (int $count) => Song::factory()
                ->for(Album::factory()->for($artist))
                ->for($artist)
                ->createMany($count),
            fn () => $this->getAs("api/artists/{$artist->id}/songs", $this->user),
        );
    }

    #[Test]
    public function listSubsonicAlbums(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlayedSongs(...),
            fn () => $this->get(
                "rest/getAlbumList2.view?apiKey={$this->user->subsonic_api_key}&f=json&type=newest&size=50",
            )->assertJsonPath('subsonic-response.status', 'ok'),
        );
    }

    #[Test]
    public function listSubsonicArtists(): void
    {
        $this->assertQueryCountStaysFlat(
            $this->createPlayedSongs(...),
            fn () => $this->get("rest/getArtists.view?apiKey={$this->user->subsonic_api_key}&f=json")->assertJsonPath(
                'subsonic-response.status',
                'ok',
            ),
        );
    }
}
