<?php

namespace Tests\Unit;

use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        @unlink(app()->publicPath().'/public/hot');
    }

    /** @test */
    public function static_urls_without_cdn_are_constructed_correctly()
    {
        // Given we are not using a CDN
        config(['koel.cdn.url' => '']);

        // When I get the static URLs for the assets
        $root = app()->staticUrl();
        $assetURL = app()->staticUrl('/foo.css ');

        // Then I see they're constructed correctly
        $this->assertEquals('http://localhost/', $root);
        $this->assertEquals('http://localhost/foo.css', $assetURL);
    }

    /** @test */
    public function static_urls_with_cdn_are_constructed_correctly()
    {
        // Given we're using a CDN
        config(['koel.cdn.url' => 'http://cdn.tld']);

        // When I get the static URLs for the assets
        $root = app()->staticUrl();
        $assetURL = app()->staticUrl('/foo.css ');

        // Then I see they're constructed correctly
        $this->assertEquals('http://cdn.tld/', $root);
        $this->assertEquals('http://cdn.tld/foo.css', $assetURL);
    }

    /** @test */
    public function application_asset_revision_urls_are_constructed_correctly_when_not_using_cdn()
    {
        // Given we have revisioned assets in the manifest file
        $manifestFile = __DIR__.'../../blobs/rev-manifest.json';

        // and we're not using a CDN
        config(['koel.cdn.url' => '']);

        // When I get the static URLs for the assets
        $assetURL = app()->rev('/foo.css', $manifestFile);

        // Then I see they're constructed correctly
        $this->assertEquals('http://localhost/public/foo00.css', $assetURL);
    }

    /** @test */
    public function application_asset_revision_urls_are_constructed_correctly_when_using_cdn()
    {
        // Given we have revisioned assets in the manifest file
        $manifestFile = __DIR__.'../../blobs/rev-manifest.json';

        // and we're using a CDN
        config(['koel.cdn.url' => 'http://cdn.tld']);

        // When I get the static URLs for the assets
        $assetURL = app()->rev('/foo.css', $manifestFile);

        // Then I see they're constructed correctly
        $this->assertEquals('http://cdn.tld/public/foo00.css', $assetURL);
    }
}
