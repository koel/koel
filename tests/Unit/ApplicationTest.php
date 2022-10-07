<?php

namespace Tests\Unit;

use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function testStaticUrlsWithoutCdnAreConstructedCorrectly(): void
    {
        config(['koel.cdn.url' => '']);

        self::assertSame('http://localhost/', static_url());
        self::assertSame('http://localhost/foo.css', static_url('/foo.css '));
    }

    public function testStaticUrlsWithCdnAreConstructedCorrectly(): void
    {
        config(['koel.cdn.url' => 'http://cdn.tld']);

        self::assertSame('http://cdn.tld/', static_url());
        self::assertSame('http://cdn.tld/foo.css', static_url('/foo.css '));
    }
}
