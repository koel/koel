<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\Lastfm;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;

class ScrobbleTest extends TestCase
{
    use WithoutMiddleware;

    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function a_song_can_be_scrobbled_via_lastfm()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $song = Song::first();

        $ts = time();

        m::mock(Lastfm::class, ['enabled' => true])
            ->shouldReceive('scrobble')
            ->with($song->album->artist->name, $song->title, $ts, $song->album->name, 'bar');

        $this->post("/api/{$song->id}/scrobble/$ts");
    }
}
