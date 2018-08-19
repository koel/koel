<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\LastfmService;
use Exception;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;

class ScrobbleTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * @throws Exception
     */
    public function testLastfmScrobble()
    {
        $this->withoutEvents();
        $this->createSampleMediaSet();

        $song = Song::first();

        $ts = time();

        m::mock(LastfmService::class, ['enabled' => true])
            ->shouldReceive('scrobble')
            ->with($song->album->artist->name, $song->title, $ts, $song->album->name, 'bar');

        $this->post("/api/{$song->id}/scrobble/$ts");
    }
}
