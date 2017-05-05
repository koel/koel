<?php

namespace Tests\Feature;

use App;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\BrowserKitTestCase;

class ApplicationTest extends BrowserKitTestCase
{
    public function setUp()
    {
        parent::setUp();
        @unlink(App::publicPath().'/public/hot');
    }

    public function testStaticUrlWithoutCDN()
    {
        config(['koel.cdn.url' => '']);

        $this->assertEquals('http://localhost/', App::staticUrl());
        $this->assertEquals('http://localhost/foo.css', App::staticUrl('/foo.css  '));
    }

    public function testStaticUrlWithCDN()
    {
        config(['koel.cdn.url' => 'http://cdn.bar']);

        $this->assertEquals('http://cdn.bar/', App::staticUrl());
        $this->assertEquals('http://cdn.bar/foo.css', App::staticUrl('/foo.css  '));
    }

    public function testRev()
    {
        config(['koel.cdn.url' => '']);

        $manifestFile = __DIR__.'../../blobs/rev-manifest.json';
        $this->assertEquals('http://localhost/public/foo00.css', App::rev('/foo.css', $manifestFile));

        config(['koel.cdn.url' => 'http://cdn.bar']);
        $this->assertEquals('http://cdn.bar/public/bar00.js', App::rev('/bar.js', $manifestFile));
    }

    public function testGetLatestVersion()
    {
        $mock = new MockHandler([
            new Response(200, [], file_get_contents(__DIR__.'../../blobs/github-tags.json')),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->assertEquals('v1.1.2', App::getLatestVersion($client));
    }
}
