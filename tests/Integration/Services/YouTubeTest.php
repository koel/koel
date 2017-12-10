<?php

namespace Tests\Integration\Services;

use App\Services\YouTube;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Tests\TestCase;

class YouTubeTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function videos_can_be_searched_from_youtube()
    {
        $this->withoutEvents();

        $client = m::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/youtube/search.json')),
        ]);

        $api = new YouTube(null, $client);
        $response = $api->search('Lorem Ipsum');

        $this->assertEquals('Slipknot - Snuff [OFFICIAL VIDEO]', $response->items[0]->snippet->title);

        // Is it cached?
        $this->assertNotNull(cache('1492972ec5c8e6b3a9323ba719655ddb'));
    }
}
