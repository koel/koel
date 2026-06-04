<?php

namespace Tests\Unit\Helpers;

use App\Exceptions\UnsafeUrlException;
use App\Helpers\SafeHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SafeHttpTest extends TestCase
{
    private SafeHttp $safeHttp;

    public function setUp(): void
    {
        parent::setUp();

        $this->safeHttp = app(SafeHttp::class);
    }

    #[Test]
    public function guzzleClientRejectsPreflightUnsafeUrl(): void
    {
        $this->expectException(UnsafeUrlException::class);

        $this->safeHttp->guzzleClient()->request('GET', 'http://127.0.0.1/admin');
    }

    #[Test]
    public function redirectOptionsRejectRedirectToPrivateHost(): void
    {
        // Build a Guzzle client that emits a 302 to a private host, then would
        // emit a 200 (which we must never reach). Apply only the redirect options
        // — no pre-flight middleware — to isolate the on_redirect leg.
        $mock = new MockHandler([
            new Response(302, ['Location' => 'http://127.0.0.1/admin']),
            new Response(200, [], 'should be unreachable'),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->expectException(UnsafeUrlException::class);

        try {
            $client->request('GET', 'https://public.example.com/feed', $this->safeHttp->redirectOptions());
        } catch (GuzzleException $e) {
            // Guzzle wraps middleware exceptions; unwrap to surface the real cause.
            if ($e->getPrevious() instanceof UnsafeUrlException) {
                throw $e->getPrevious();
            }

            throw $e;
        }
    }

    #[Test]
    public function redirectOptionsAllowRedirectToPublicHost(): void
    {
        $mock = new MockHandler([
            new Response(302, ['Location' => 'https://other.example.com/feed']),
            new Response(200, [], 'ok'),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $response = $client->request('GET', 'https://public.example.com/feed', $this->safeHttp->redirectOptions());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', (string) $response->getBody());
    }
}
