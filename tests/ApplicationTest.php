<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ApplicationTest extends TestCase
{
    public function testStaticUrlWithoutCDN()
    {
        putenv('CDN_URL');

        $this->assertEquals(App::staticUrl(), '/');
        $this->assertEquals(App::staticUrl('foo.css  '), '/foo.css');
    }

    public function testStaticUrlWithCDN()
    {
        putenv('CDN_URL=http://cdn.bar');

        $this->assertEquals(App::staticUrl(), 'http://cdn.bar/');
        $this->assertEquals(App::staticUrl('foo.css  '), 'http://cdn.bar/foo.css');
    }

    public function testRev()
    {
        putenv('CDN_URL');

        $manifestFile = dirname(__FILE__) . '/blobs/rev-manifest.json';
        $this->assertEquals(App::rev('foo.css', $manifestFile), '/public/build/foo00.css');

        putenv('CDN_URL=http://cdn.bar');
        $this->assertEquals(App::rev('bar.js', $manifestFile), 'http://cdn.bar/public/build/bar00.js');
    }

    public function testGetLatestVersion()
    {
        $mock = new MockHandler([
            new Response(200, [], file_get_contents(dirname(__FILE__) . '/blobs/github-tags.json')),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->assertEquals('v1.1.2', App::getLatestVersion($client));
    }
}
