<?php

namespace Tests\Feature;

use App\Facades\Dispatcher;
use App\Jobs\ScrobbleJob;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ScrobbleTest extends TestCase
{
    #[Test]
    public function lastfmScrobble(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->andReturnUsing(function (ScrobbleJob $job) use ($song, $user): void {
            $this->assertTrue($song->is($job->song));
            $this->assertTrue($user->is($job->user));
            self::assertEquals(100, $job->timestamp);
        });

        $this->postAs("/api/songs/{$song->id}/scrobble", ['timestamp' => 100], $user)->assertNoContent();
    }

    #[Test]
    public function listenbrainzScrobble(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->andReturnUsing(function (ScrobbleJob $job) use ($song, $user): void {
            $this->assertTrue($song->is($job->song));
            $this->assertTrue($user->is($job->user));
            self::assertEquals(100, $job->timestamp);
        });

        $this->postAs("/api/songs/{$song->id}/scrobble", ['timestamp' => 100], $user)->assertNoContent();
    }

    #[Test]
    public function noScrobbleWithoutAConnectedService(): void
    {
        $user = create_user(['preferences' => []]);
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->never();

        $this->postAs("/api/songs/{$song->id}/scrobble", ['timestamp' => 100], $user)->assertNoContent();
    }

    #[Test]
    public function noScrobbleForUnknownArtist(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);
        $artist = Artist::factory()->createOne(['name' => Artist::UNKNOWN_NAME]);
        $song = Song::factory()->for($artist)->createOne();

        Dispatcher::expects('dispatch')->never();

        $this->postAs("/api/songs/{$song->id}/scrobble", ['timestamp' => 100], $user)->assertNoContent();
    }
}
