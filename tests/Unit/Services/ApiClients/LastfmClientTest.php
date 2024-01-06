<?php

namespace Tests\Unit\Services\ApiClients;

use App\Services\ApiClients\LastfmClient;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LastfmClientTest extends TestCase
{
    public function testGetSessionKey(): void
    {
        $mock = new MockHandler([new Response(200, [], File::get(test_path('blobs/lastfm/session-key.json')))]);

        $client = new LastfmClient(new GuzzleHttpClient(['handler' => HandlerStack::create($mock)]));

        self::assertSame('foo', $client->getSessionKey('bar'));
    }
}
