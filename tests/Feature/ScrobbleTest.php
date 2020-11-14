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
        static::createSampleMediaSet();

        $song = Song::first();
        /** @var User $user */
        $user = User::factory()->create();
        $user->setPreference('lastfm_session_key', 'foo');

        $timestamp = time();

        static::mockIocDependency(LastfmService::class)
            ->shouldReceive('scrobble')
            ->with($song->album->artist->name, $song->title, $timestamp, $song->album->name, 'foo')
            ->once();

        $response = $this->postAsUser("/api/{$song->id}/scrobble", ['timestamp' => $timestamp], $user);

        $response->assertStatus(204);
    }
}
