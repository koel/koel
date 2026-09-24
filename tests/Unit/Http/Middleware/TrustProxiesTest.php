<?php

namespace Tests\Unit\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    #[Test]
    public function trustsForwardedHeadersFromProxyOnPrivateNetwork(): void
    {
        $request = self::handleRequestFrom('10.0.0.2');

        self::assertSame('203.0.113.7', $request->ip());
        self::assertSame('forwarded.example', $request->getHost());
    }

    #[Test]
    public function ignoresForwardedHeadersFromPublicClient(): void
    {
        $request = self::handleRequestFrom('8.8.4.4');

        self::assertSame('8.8.4.4', $request->ip());
        self::assertSame('music.example.com', $request->getHost());
    }

    #[Test]
    public function trustsConfiguredProxies(): void
    {
        config(['trustedproxy.proxies' => '8.8.4.4']);

        self::assertSame('203.0.113.7', self::handleRequestFrom('8.8.4.4')->ip());
    }

    private static function handleRequestFrom(string $remoteAddress): Request
    {
        $request = Request::create('http://music.example.com/', server: [
            'REMOTE_ADDR' => $remoteAddress,
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
            'HTTP_X_FORWARDED_HOST' => 'forwarded.example',
        ]);

        (new TrustProxies())->handle($request, static fn () => null);

        return $request;
    }

    public function tearDown(): void
    {
        Request::setTrustedProxies([], 0);

        parent::tearDown();
    }
}
