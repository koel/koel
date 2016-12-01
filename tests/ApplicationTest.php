<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ApplicationTest extends TestCase
{
    public function testStaticUrlWithoutCDN()
    {
        config(['koel.cdn.url' => '']);

        $this->assertEquals(App::staticUrl(), 'http://localhost/');
        $this->assertEquals(App::staticUrl('foo.css  '), 'http://localhost/foo.css');
    }

    public function testStaticUrlWithCDN()
    {
        config(['koel.cdn.url' => 'http://cdn.bar']);

        $this->assertEquals(App::staticUrl(), 'http://cdn.bar/');
        $this->assertEquals(App::staticUrl('foo.css  '), 'http://cdn.bar/foo.css');
    }

    public function testRev()
    {
        config(['koel.cdn.url' => '']);

        $manifestFile = __DIR__.'/blobs/rev-manifest.json';
        $this->assertEquals(App::rev('foo.css', $manifestFile), 'http://localhost/public/build/foo00.css');

        config(['koel.cdn.url' => 'http://cdn.bar']);
        $this->assertEquals(App::rev('bar.js', $manifestFile), 'http://cdn.bar/public/build/bar00.js');
    }

    public function testGetLatestVersion()
    {
        $mock = new MockHandler([
            new Response(200, [], file_get_contents(__DIR__.'/blobs/github-tags.json')),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->assertEquals('v1.1.2', App::getLatestVersion($client));
    }
}
