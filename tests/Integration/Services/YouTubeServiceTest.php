<?php

namespace Tests\Integration\Services;

use App\Services\YouTubeService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\Logger;
use Mockery;
use Tests\TestCase;

class YouTubeServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSearch(): void
    {
        $this->withoutEvents();

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/youtube/search.json')),
        ]);

        $api = new YouTubeService($client, app(Repository::class), app(Logger::class));
        $response = $api->search('Lorem Ipsum');

        $this->assertEquals('Slipknot - Snuff [OFFICIAL VIDEO]', $response->items[0]->snippet->title);
        $this->assertNotNull(cache('1492972ec5c8e6b3a9323ba719655ddb'));
    }
}
