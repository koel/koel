<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    #[Test]
    public function staticUrlsWithoutCdnAreConstructedCorrectly(): void
    {
        config(['koel.cdn.url' => '']);

        self::assertSame('http://localhost/', static_url());
        self::assertSame('http://localhost/foo.css', static_url('/foo.css '));
    }

    #[Test]
    public function staticUrlsWithCdnAreConstructedCorrectly(): void
    {
        config(['koel.cdn.url' => 'http://cdn.tld']);

        self::assertSame('http://cdn.tld/', static_url());
        self::assertSame('http://cdn.tld/foo.css', static_url('/foo.css '));
    }
}
