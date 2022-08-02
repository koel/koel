<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;

class ScrobbleTest extends TestCase
{
    public function testLastfmScrobble(): void
    {
        $this->withoutEvents();

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Song $song */
        $song = Song::factory()->create();

        $timestamp = time();

        self::mock(LastfmService::class)
            ->shouldReceive('scrobble')
            ->with($song->album->artist->name, $song->title, $timestamp, $song->album->name, $user->lastfm_session_key)
            ->once();

        $this->postAs("/api/$song->id/scrobble", ['timestamp' => $timestamp], $user)
            ->assertNoContent();
    }
}
