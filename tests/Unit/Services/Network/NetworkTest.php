<?php

namespace Tests\Unit\Services\Network;

use App\Services\Network\Network;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    private Network $network;

    public function setUp(): void
    {
        parent::setUp();

        $this->network = new Network();
    }

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
        self::assertFalse($this->network->isPublicHost($host));
    }

    #[Test]
    public function acceptsPublicHost(): void
    {
        // example.com is IANA-reserved and always resolves to a public IP
        self::assertTrue($this->network->isPublicHost('example.com'));
    }

    #[Test]
    public function rejectsUnresolvableHost(): void
    {
        self::assertFalse($this->network->isPublicHost('this-host-does-not-exist.invalid'));
    }

    #[Test]
    public function isSafeUrlAcceptsPublicHttpUrl(): void
    {
        self::assertTrue($this->network->isSafeUrl('https://example.com/feed.xml'));
        self::assertTrue($this->network->isSafeUrl('http://example.com/feed.xml'));
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
        self::assertFalse($this->network->isSafeUrl($url));
    }

    /** @return array<string, array{string}> */
    public static function provideIpv6TransitionWrappers(): array
    {
        return [
            'NAT64 well-known of loopback' => ['64:ff9b::7f00:1'],
            'NAT64 well-known of AWS metadata' => ['64:ff9b::a9fe:a9fe'],
            'NAT64 well-known of private 10.x' => ['64:ff9b::a00:1'],
            '6to4 of private 10.0.0.1' => ['2002:a00:1::'],
            '6to4 of loopback' => ['2002:7f00:1::'],
            '6to4 of AWS metadata' => ['2002:a9fe:a9fe::'],
        ];
    }

    #[Test, DataProvider('provideIpv6TransitionWrappers')]
    public function rejectsIpv6TransitionWrappersOfPrivateIps(string $wrapper): void
    {
        self::assertFalse($this->network->isPublicHost($wrapper));
        self::assertSame([], $this->network->resolveToPublicIps($wrapper));
    }

    #[Test]
    public function resolveToPublicIpsReturnsIpsForPublicIpLiteral(): void
    {
        self::assertSame(['8.8.8.8'], $this->network->resolveToPublicIps('8.8.8.8'));
    }

    #[Test]
    public function resolveToPublicIpsReturnsEmptyForPrivateIpLiteral(): void
    {
        self::assertSame([], $this->network->resolveToPublicIps('127.0.0.1'));
    }

    #[Test]
    public function resolveToPublicIpsReturnsIpsForPublicHost(): void
    {
        $ips = $this->network->resolveToPublicIps('example.com');

        self::assertNotEmpty($ips);

        foreach ($ips as $ip) {
            self::assertNotFalse(filter_var($ip, FILTER_VALIDATE_IP));
        }
    }

    #[Test]
    public function resolveToPublicIpsReturnsEmptyForUnresolvableHost(): void
    {
        self::assertSame([], $this->network->resolveToPublicIps('this-host-does-not-exist.invalid'));
    }
}
