<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Tests\TestCase;

class TrustHostsTest extends TestCase
{
    #[Test]
    public function acceptsAnyHostWhenNoTrustedHostsAreConfigured(): void
    {
        config(['app.trusted_hosts' => ['']]);

        self::assertSame('anything.example', $this->handleRequestFor('anything.example')->getHost());
    }

    #[Test]
    public function acceptsTrustedHost(): void
    {
        config(['app.trusted_hosts' => ['music.example.com', 'localhost']]);

        self::assertSame('music.example.com', $this->handleRequestFor('music.example.com')->getHost());
    }

    #[Test]
    public function rejectsUntrustedHost(): void
    {
        config(['app.trusted_hosts' => ['music.example.com']]);

        $this->expectException(SuspiciousOperationException::class);

        $this->handleRequestFor('evil.example')->getHost();
    }

    #[Test]
    public function rejectsHostThatOnlyContainsATrustedHost(): void
    {
        config(['app.trusted_hosts' => ['music.example.com']]);

        $this->expectException(SuspiciousOperationException::class);

        $this->handleRequestFor('music.example.com.evil.example')->getHost();
    }

    #[Test]
    public function acceptsAnySubdomainOfAWildcardHost(): void
    {
        config(['app.trusted_hosts' => ['*.example.com']]);

        self::assertSame('music.example.com', $this->handleRequestFor('music.example.com')->getHost());
        self::assertSame('a.b.example.com', $this->handleRequestFor('a.b.example.com')->getHost());
    }

    #[Test]
    public function rejectsTheBareDomainOfAWildcardHost(): void
    {
        config(['app.trusted_hosts' => ['*.example.com']]);

        $this->expectException(SuspiciousOperationException::class);

        $this->handleRequestFor('example.com')->getHost();
    }

    #[Test]
    public function rejectsALookAlikeOfAWildcardHost(): void
    {
        config(['app.trusted_hosts' => ['*.example.com']]);

        $this->expectException(SuspiciousOperationException::class);

        $this->handleRequestFor('evil-example.com')->getHost();
    }

    #[Test]
    public function treatsAnAsteriskElsewhereAsALiteralCharacter(): void
    {
        config(['app.trusted_hosts' => ['music.*.com']]);

        $this->expectException(SuspiciousOperationException::class);

        $this->handleRequestFor('music.example.com')->getHost();
    }

    private function handleRequestFor(string $host): Request
    {
        $this->app['env'] = 'production';

        $request = Request::create("http://$host/");
        (new TrustHosts($this->app))->handle($request, static fn () => null);

        return $request;
    }

    public function tearDown(): void
    {
        Request::setTrustedHosts([]);
        $this->app['env'] = 'testing';

        parent::tearDown();
    }
}
