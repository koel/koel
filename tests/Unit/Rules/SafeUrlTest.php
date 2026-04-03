<?php

namespace Tests\Unit\Rules;

use App\Rules\SafeUrl;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SafeUrlTest extends TestCase
{
    private SafeUrl $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new SafeUrl();
    }

    private function passes(string $url): bool
    {
        $failed = false;

        $this->rule->validate('url', $url, static function () use (&$failed) { // @phpstan-ignore argument.type
            $failed = true;
        });

        return !$failed;
    }

    #[Test]
    public function rejectsPrivateIpAddresses(): void
    {
        self::assertFalse($this->passes('http://127.0.0.1/feed'));
        self::assertFalse($this->passes('http://10.0.0.1/feed'));
        self::assertFalse($this->passes('http://192.168.1.1/feed'));
        self::assertFalse($this->passes('http://172.16.0.1/feed'));
    }

    #[Test]
    public function rejectsLoopbackAddress(): void
    {
        self::assertFalse($this->passes('http://127.0.0.1/feed'));
        self::assertFalse($this->passes('http://[::1]/feed'));
    }

    #[Test]
    public function rejectsNonHttpSchemes(): void
    {
        self::assertFalse($this->passes('ftp://example.com/feed'));
        self::assertFalse($this->passes('file:///etc/passwd'));
        self::assertFalse($this->passes('gopher://example.com/feed'));
    }

    #[Test]
    public function rejectsUrlsWithNoHost(): void
    {
        self::assertFalse($this->passes('http:///path'));
    }

    #[Test]
    public function acceptsPublicIpUrls(): void
    {
        // 8.8.8.8 (Google DNS) is a known public IP
        self::assertTrue($this->passes('https://8.8.8.8/feed'));
        self::assertTrue($this->passes('http://1.1.1.1/feed.xml'));
    }
}
