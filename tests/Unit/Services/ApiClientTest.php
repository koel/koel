<?php

namespace Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;
use Tests\TestCase;
use Tests\Unit\Stubs\ConcreteApiClient;

class ApiClientTest extends TestCase
{
    use WithoutMiddleware;

    public function testBuildUri()
    {
        /** @var Client $client */
        $client = m::mock(Client::class);
        $api = new ConcreteApiClient($client);

        $this->assertEquals('http://foo.com/get/param?key=bar', $api->buildUrl('get/param'));
        $this->assertEquals('http://foo.com/get/param?baz=moo&key=bar', $api->buildUrl('/get/param?baz=moo'));
        $this->assertEquals('http://baz.com/?key=bar', $api->buildUrl('http://baz.com/'));
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
        $client = m::mock(Client::class, [
            $method => new Response(200, [], $responseBody),
        ]);

        $api = new ConcreteApiClient($client);

        $this->assertSame((array) json_decode($responseBody), (array) $api->$method('/'));
    }
}
