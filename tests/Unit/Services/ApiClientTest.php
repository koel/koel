<?php

namespace Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Log\Logger;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Stubs\ConcreteApiClient;

class ApiClientTest extends TestCase
{
    use WithoutMiddleware;

    /** @var Cache|MockInterface */
    private $cache;

    /** @var Client|MockInterface */
    private $client;

    /** @var Logger|MockInterface */
    private $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(Client::class);
        $this->cache = Mockery::mock(Cache::class);
        $this->logger = Mockery::mock(Logger::class);
    }

    public function testBuildUri(): void
    {
        $api = new ConcreteApiClient($this->client, $this->cache, $this->logger);

        self::assertEquals('http://foo.com/get/param?key=bar', $api->buildUrl('get/param'));
        self::assertEquals('http://foo.com/get/param?baz=moo&key=bar', $api->buildUrl('/get/param?baz=moo'));
        self::assertEquals('http://baz.com/?key=bar', $api->buildUrl('http://baz.com/'));
    }

    public function provideRequestData(): array
    {
        return [
            ['get', '{"foo":"bar"}'],
            ['post', '{"foo":"bar"}'],
            ['put', '{"foo":"bar"}'],
            ['delete', '{"foo":"bar"}'],
        ];
    }

    /**
     * @dataProvider provideRequestData
     */
    public function testRequest(string $method, string $responseBody): void
    {
        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            $method => new Response(200, [], $responseBody),
        ]);

        $api = new ConcreteApiClient($client, $this->cache, $this->logger);

        self::assertSame((array) json_decode($responseBody), (array) $api->$method('/'));
    }
}
