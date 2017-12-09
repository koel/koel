<?php

namespace Tests\Unit\Services;

use App\Services\RESTfulService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;
use Tests\TestCase;

class RESTfulAPIServiceTest extends TestCase
{
    use WithoutMiddleware;

    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function a_uri_can_be_constructed()
    {
        /** @var Client $client */
        $client = m::mock(Client::class);
        $api = new RESTfulService('bar', null, 'http://foo.com', $client);
        $this->assertEquals('http://foo.com/get/param?key=bar', $api->buildUrl('get/param'));
        $this->assertEquals('http://foo.com/get/param?baz=moo&key=bar', $api->buildUrl('/get/param?baz=moo'));
        $this->assertEquals('http://baz.com/?key=bar', $api->buildUrl('http://baz.com/'));
    }

    /** @test */
    public function a_request_can_be_made()
    {
        /** @var Client $client */
        $client = m::mock(Client::class, [
            'get' => new Response(200, [], '{"foo":"bar"}'),
            'post' => new Response(200, [], '{"foo":"bar"}'),
            'delete' => new Response(200, [], '{"foo":"bar"}'),
            'put' => new Response(200, [], '{"foo":"bar"}'),
        ]);

        $api = new RESTfulService('foo', null, 'http://foo.com', $client);

        $this->assertObjectHasAttribute('foo', $api->get('/'));
        $this->assertObjectHasAttribute('foo', $api->post('/'));
        $this->assertObjectHasAttribute('foo', $api->put('/'));
        $this->assertObjectHasAttribute('foo', $api->delete('/'));
    }
}
