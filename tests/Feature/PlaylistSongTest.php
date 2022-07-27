<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;

class PlaylistSongTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        static::createSampleMediaSet();
    }

    public function testUpdatePlaylistSongs(): void
    {
        $this->doTestUpdatePlaylistSongs();
    }

    /** @deprecated  */
    public function testSyncPlaylist(): void
    {
        $this->doTestUpdatePlaylistSongs(true);
    }

    private function doTestUpdatePlaylistSongs(bool $useDeprecatedRoute = false): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();

        $toRemainSongs = Song::factory(3)->create();
        $toBeRemovedSongs = Song::factory(2)->create();
        $playlist->songs()->attach($toRemainSongs->merge($toBeRemovedSongs));

        $path = $useDeprecatedRoute ? "api/playlist/$playlist->id/sync" : "api/playlist/$playlist->id/songs";

        $this->putAs($path, ['songs' => $toRemainSongs->pluck('id')->all()], $user)->assertOk();

        self::assertEqualsCanonicalizing(
            $toRemainSongs->pluck('id')->all(),
            $playlist->refresh()->songs->pluck('id')->all()
        );
    }

    public function testGetPlaylistSongs(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $songs = Song::factory(2)->create();
        $playlist->songs()->saveMany($songs);

        $this->getAs("api/playlist/$playlist->id/songs", $playlist->user)
            ->assertJson($songs->pluck('id')->all());
    }
}
