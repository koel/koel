<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Mockery;

class ScrobbleTest extends TestCase
{
    public function testLastfmScrobble()
    {
        $song = factory(Song::class)->create();
        $user = factory(User::class)->create();

        $ts = time();

        $lastfm = Mockery::mock(LastfmService::class)->makePartial();
        $lastfm->shouldReceive('enabled')->andReturn(true);
        $lastfm->shouldReceive('getUserSessionKey')->andReturn('foo');
        $lastfm->shouldReceive('scrobble')
            ->with($song->album->artist->name, $song->title, $ts, $song->album->name, 'foo')
            ->once();

        app()->instance(LastfmService::class, $lastfm);

        $this->postAsUser("/api/{$song->id}/scrobble/$ts", [], $user)
            ->assertResponseOk();
    }
}
