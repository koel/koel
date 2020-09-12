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

    /** @test */
    public function static_urls_without_cdn_are_constructed_correctly(): void
    {
        // Given we are not using a CDN
        config(['koel.cdn.url' => '']);

        // When I get the static URLs for the assets
        $root = app()->staticUrl();
        $assetURL = app()->staticUrl('/foo.css ');

        // Then I see they're constructed correctly
        self::assertEquals('http://localhost/', $root);
        self::assertEquals('http://localhost/foo.css', $assetURL);
    }

    /** @test */
    public function static_urls_with_cdn_are_constructed_correctly(): void
    {
        // Given we're using a CDN
        config(['koel.cdn.url' => 'http://cdn.tld']);

        // When I get the static URLs for the assets
        $root = app()->staticUrl();
        $assetURL = app()->staticUrl('/foo.css ');

        // Then I see they're constructed correctly
        self::assertEquals('http://cdn.tld/', $root);
        self::assertEquals('http://cdn.tld/foo.css', $assetURL);
    }

    /** @test */
    public function application_asset_revision_urls_are_constructed_correctly_when_not_using_cdn(): void
    {
        // Given we have revisioned assets in the manifest file
        $manifestFile = __DIR__.'../../blobs/rev-manifest.json';

        // and we're not using a CDN
        config(['koel.cdn.url' => '']);

        // When I get the static URLs for the assets
        $assetURL = app()->rev('/foo.css', $manifestFile);

        // Then I see they're constructed correctly
        self::assertEquals('http://localhost/foo00.css', $assetURL);
    }

    /** @test */
    public function application_asset_revision_urls_are_constructed_correctly_when_using_cdn(): void
    {
        // Given we have revisioned assets in the manifest file
        $manifestFile = __DIR__.'../../blobs/rev-manifest.json';

        // and we're using a CDN
        config(['koel.cdn.url' => 'http://cdn.tld']);

        // When I get the static URLs for the assets
        $assetURL = app()->rev('/foo.css', $manifestFile);

        // Then I see they're constructed correctly
        self::assertEquals('http://cdn.tld/foo00.css', $assetURL);
    }
}
