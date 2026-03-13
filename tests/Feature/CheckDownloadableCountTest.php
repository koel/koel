<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Favorite;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class CheckDownloadableCountTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['koel.download.limit' => 0]);
    }

    #[Test]
    public function checkSongs(): void
    {
        $songs = Song::factory()->createMany(3);

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'songs',
                    'ids' => $songs->pluck('id')->all(),
                ]),
        )->assertNoContent();
    }

    #[Test]
    public function checkAlbum(): void
    {
        $album = Album::factory()->createOne();
        Song::factory()->for($album)->createMany(5);

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'album',
                    'id' => $album->id,
                ]),
        )->assertNoContent();
    }

    #[Test]
    public function checkArtist(): void
    {
        $artist = Artist::factory()->createOne();
        Song::factory()
            ->for($artist)
            ->for(Album::factory())
            ->createMany(4);

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'artist',
                    'id' => $artist->id,
                ]),
        )->assertNoContent();
    }

    #[Test]
    public function checkPlaylist(): void
    {
        $user = create_user();
        $playlist = create_playlist();
        $playlist->users()->detach();
        $playlist->users()->attach($user, ['role' => 'owner']);
        $songs = Song::factory()->createMany(3);
        $playlist->playables()->attach($songs, ['user_id' => $user->id]);

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'playlist',
                    'id' => $playlist->id,
                ]),
            $user,
        )->assertNoContent();
    }

    #[Test]
    public function checkFavorites(): void
    {
        $user = create_user();
        $songs = Song::factory()->createMany(2);

        $songs->each(static fn (Song $song) => Favorite::factory()->for($user)->createOne([
            'favoriteable_id' => $song->id,
        ]));

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'favorites',
                ]),
            $user,
        )->assertNoContent();
    }

    #[Test]
    public function forbidWhenLimitExceeded(): void
    {
        config(['koel.download.limit' => 2]);

        $songs = Song::factory()->createMany(3);

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'songs',
                    'ids' => $songs->pluck('id')->all(),
                ]),
        )->assertForbidden();
    }

    #[Test]
    public function allowWhenLimitIsZero(): void
    {
        config(['koel.download.limit' => 0]);

        $songs = Song::factory()->createMany(10);

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'songs',
                    'ids' => $songs->pluck('id')->all(),
                ]),
        )->assertNoContent();
    }

    #[Test]
    public function allowWhenWithinLimit(): void
    {
        config(['koel.download.limit' => 5]);

        $songs = Song::factory()->createMany(5);

        $this->getAs(
            'api/download/check?'
                . http_build_query([
                    'type' => 'songs',
                    'ids' => $songs->pluck('id')->all(),
                ]),
        )->assertNoContent();
    }
}
