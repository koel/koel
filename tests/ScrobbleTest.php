<?php

use App\Models\Song;
use App\Models\User;
use App\Services\Lastfm;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;

class ScrobbleTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testScrobble()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $song = Song::first();

        $ts = time();

        $lastfm = m::mock(Lastfm::class, ['enabled' => true]);
        $lastfm->shouldReceive('scrobble')
            ->with($song->album->artist->name, $song->title, $ts, $song->album->name, 'bar');

        $this->post("/api/{$song->id}/scrobble/$ts");
    }
}
