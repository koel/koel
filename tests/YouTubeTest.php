<?php

use App\Models\Song;
use App\Services\YouTube;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;
use YouTube as YouTubeFacade;

class YouTubeTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testSearch()
    {
        $this->withoutEvents();

        $client = m::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(dirname(__FILE__).'/blobs/youtube/search.json')),
        ]);

        $api = new YouTube(null, $client);
        $response = $api->search('Lorem Ipsum');

        $this->assertEquals('Slipknot - Snuff [OFFICIAL VIDEO]', $response->items[0]->snippet->title);

        // Is it cached?
        $this->assertNotNull(Cache::get('1492972ec5c8e6b3a9323ba719655ddb'));
    }

    public function testSearchVideosRelatedToSong()
    {
        $this->createSampleMediaSet();
        $song = Song::first();

        // We test on the facade here
        YouTubeFacade::shouldReceive('searchVideosRelatedToSong')->once();

        $this->visit("/api/youtube/search/song/{$song->id}");
    }
}
