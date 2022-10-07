<?php

namespace Tests\Unit\Services\ApiClients;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Stubs\ConcreteApiClient;

class ApiClientTest extends TestCase
{
    use WithoutMiddleware;

    private Client|LegacyMockInterface|MockInterface $wrapped;

    public function setUp(): void
    {
        parent::setUp();

        $this->wrapped = Mockery::mock(Client::class);
    }

    public function testBuildUri(): void
    {
        $api = new ConcreteApiClient($this->wrapped);

        self::assertSame('https://foo.com/get/param?key=bar', $api->buildUrl('get/param'));
        self::assertSame('https://foo.com/get/param?baz=moo&key=bar', $api->buildUrl('/get/param?baz=moo'));
        self::assertSame('https://baz.com/?key=bar', $api->buildUrl('https://baz.com/'));
    }

    /** @return array<mixed> */
    public function provideRequestData(): array
    {
        return [
            ['get', '{"foo":"bar"}'],
            ['post', '{"foo":"bar"}'],
            ['put', '{"foo":"bar"}'],
            ['delete', '{"foo":"bar"}'],
        ];
    }

    /** @dataProvider provideRequestData */
    public function testRequest(string $method, string $responseBody): void
    {
        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            $method => new Response(200, [], $responseBody),
        ]);

        $api = new ConcreteApiClient($client);

        self::assertSame((array) json_decode($responseBody), (array) $api->$method('/'));
    }
}
