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
}
