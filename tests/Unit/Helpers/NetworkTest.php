<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Network;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function providePrivateHosts(): array
    {
        return [
            'loopback IPv4' => ['127.0.0.1'],
            'private 192.168.x' => ['192.168.1.1'],
            'private 10.x' => ['10.0.0.1'],
            'link-local' => ['169.254.1.1'],
            'loopback IPv6' => ['::1'],
        ];
    }

    #[Test, DataProvider('providePrivateHosts')]
    public function rejectsPrivateIps(string $host): void
    {
        self::assertFalse(Network::isPublicHost($host));
    }

    #[Test]
    public function acceptsPublicHost(): void
    {
        // example.com is IANA-reserved and always resolves to a public IP
        self::assertTrue(Network::isPublicHost('example.com'));
    }

    #[Test]
    public function rejectsUnresolvableHost(): void
    {
        self::assertFalse(Network::isPublicHost('this-host-does-not-exist.invalid'));
    }

    #[Test]
    public function isSafeUrlAcceptsPublicHttpUrl(): void
    {
        self::assertTrue(Network::isSafeUrl('https://example.com/feed.xml'));
        self::assertTrue(Network::isSafeUrl('http://example.com/feed.xml'));
    }

    /** @return array<string, array{string}> */
    public static function provideUnsafeUrls(): array
    {
        return [
            'AWS metadata IP' => ['http://169.254.169.254/latest/meta-data/'],
            'loopback IPv4' => ['http://127.0.0.1/admin'],
            'private 192.168.x' => ['http://192.168.1.1/secrets'],
            'private 10.x' => ['http://10.0.0.1/internal'],
            'loopback IPv6' => ['http://[::1]/admin'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/feed'],
            'gopher scheme' => ['gopher://example.com/feed'],
            'no scheme' => ['example.com/feed'],
            'no host' => ['http:///path'],
            'empty string' => [''],
            'garbage' => ['not a url at all'],
        ];
    }

    #[Test, DataProvider('provideUnsafeUrls')]
    public function isSafeUrlRejectsUnsafeUrl(string $url): void
    {
        self::assertFalse(Network::isSafeUrl($url));
    }
}
