<?php

namespace Tests\Integration\Services;

use App\Services\ApplicationInformationService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Tests\TestCase;

class ApplicationInformationServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetLatestVersionNumber(): void
    {
        $latestVersion = 'v1.1.2';

        $mock = new MockHandler([
            new Response(200, [], file_get_contents(__DIR__.'../../../blobs/github-tags.json')),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new ApplicationInformationService($client, app(Cache::class), app(Logger::class));

        self::assertEquals($latestVersion, $service->getLatestVersionNumber());
        self::assertSame($latestVersion, cache('latestKoelVersion'));
    }
}
