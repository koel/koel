<?php

namespace Tests\Unit;

use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        @unlink(public_path('hot'));
    }

    public function testStaticUrlsWithoutCdnAreConstructedCorrectly(): void
    {
        config(['koel.cdn.url' => '']);

        self::assertEquals('http://localhost/', static_url());
        self::assertEquals('http://localhost/foo.css', static_url('/foo.css '));
    }

    public function testStaticUrlsWithCdnAreConstructedCorrectly(): void
    {
        config(['koel.cdn.url' => 'http://cdn.tld']);

        self::assertEquals('http://cdn.tld/', static_url());
        self::assertEquals('http://cdn.tld/foo.css', static_url('/foo.css '));
    }

    public function testApplicationAssetRevisionUrlsAreConstructedCorrectlyWhenNotUsingCdn(): void
    {
        $manifestFile = __DIR__ . '../../blobs/rev-manifest.json';
        config(['koel.cdn.url' => '']);

        self::assertEquals('http://localhost/foo00.css', app()->rev('/foo.css', $manifestFile));
    }

    public function testApplicationAssetRevisionUrlsAreConstructedCorrectlyWhenUsingCdn(): void
    {
        $manifestFile = __DIR__ . '../../blobs/rev-manifest.json';
        config(['koel.cdn.url' => 'http://cdn.tld']);

        self::assertEquals('http://cdn.tld/foo00.css', app()->rev('/foo.css', $manifestFile));
    }
}
