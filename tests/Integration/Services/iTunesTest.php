<?php

namespace Tests\Integration\Services;

use App\Services\iTunes;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Tests\TestCase;

class iTunesTest extends TestCase
{
    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_gets_itunes_track_url()
    {
        // Given there's a search term
        $term = 'Foo Bar';

        // When I request the iTunes track URL for the song
        $client = m::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/itunes/track.json')),
        ]);

        $url = (new iTunes($client))->getTrackUrl($term);

        // Then I retrieve the track URL
        $this->assertEquals('https://itunes.apple.com/us/album/i-remember-you/id265611220?i=265611396&uo=4&at=1000lsGu',
            $url);

        // And the track url is cached
        $this->assertNotNull(cache('b57a14784d80c58a856e0df34ff0c8e2'));
    }
}
