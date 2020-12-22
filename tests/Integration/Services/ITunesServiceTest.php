<?php

namespace Tests\Integration\Services;

use App\Services\ITunesService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Mockery;
use Tests\TestCase;

class ITunesServiceTest extends TestCase
{
    public function testGetTrackUrl(): void
    {
        $term = 'Foo Bar';

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__ . '../../../blobs/itunes/track.json')),
        ]);

        $cache = app(Cache::class);
        $logger = app(Logger::class);

        $url = (new ITunesService($client, $cache, $logger))->getTrackUrl($term);

        self::assertEquals(
            'https://itunes.apple.com/us/album/i-remember-you/id265611220?i=265611396&uo=4&at=1000lsGu',
            $url
        );

        self::assertNotNull(cache('b57a14784d80c58a856e0df34ff0c8e2'));
    }
}
