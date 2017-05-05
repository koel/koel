<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\Lastfm;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;
use Tests\BrowserKitTestCase;

class ScrobbleTest extends BrowserKitTestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testScrobble()
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
