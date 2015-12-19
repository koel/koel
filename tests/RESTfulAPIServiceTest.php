<?php

use App\Services\RESTfulService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class RESTfulAPIServiceTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testUrlConstruction()
    {
        $api = new RESTfulService('bar', null, 'http://foo.com', \Mockery::mock(Client::class));
        $this->assertEquals('http://foo.com/get/param?key=bar', $api->buildUrl('get/param'));
        $this->assertEquals('http://foo.com/get/param?baz=moo&key=bar', $api->buildUrl('/get/param?baz=moo'));
        $this->assertEquals('http://baz.com/?key=bar', $api->buildUrl('http://baz.com/'));
    }

    public function testRequest()
    {
        $client = \Mockery::mock(Client::class, [
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
