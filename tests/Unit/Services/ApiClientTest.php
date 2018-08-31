<?php

namespace Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Log\Logger;
use Mockery;
use Tests\TestCase;
use Tests\Unit\Stubs\ConcreteApiClient;

class ApiClientTest extends TestCase
{
    use WithoutMiddleware;

    /** @var Cache */
    private $cache;

    /** @var Client */
    private $client;

    /** @var Logger */
    private $logger;

    public function setUp()
    {
        parent::setUp();

        /**
         * @var Client client
         * @var Cache cache
         * @var Logger logger
         */
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

    public function provideRequestData()
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
     *
     * @param $method
     * @param $responseBody
     */
    public function testRequest($method, $responseBody)
    {
        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            $method => new Response(200, [], $responseBody),
        ]);

        $api = new ConcreteApiClient($client, $this->cache, $this->logger);

        self::assertSame((array) json_decode($responseBody), (array) $api->$method('/'));
    }
}
