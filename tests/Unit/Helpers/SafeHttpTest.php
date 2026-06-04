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

    #[Test]
    public function pinnedOptionsEmitsCurlResolveForResolvedHost(): void
    {
        $options = $this->safeHttp->pinnedOptions('https://example.com/feed');

        self::assertArrayHasKey('curl', $options);
        self::assertArrayHasKey(CURLOPT_RESOLVE, $options['curl']);

        foreach ($options['curl'][CURLOPT_RESOLVE] as $entry) {
            self::assertMatchesRegularExpression('/^example\.com:443:[0-9a-f.:]+$/i', $entry);
        }
    }

    #[Test]
    public function pinnedOptionsSkipsResolveForIpLiteralUrl(): void
    {
        $options = $this->safeHttp->pinnedOptions('https://8.8.8.8/');

        self::assertArrayNotHasKey('curl', $options);
    }

    #[Test]
    public function pinnedOptionsRejectsPrivateIpLiteral(): void
    {
        $this->expectException(UnsafeUrlException::class);

        $this->safeHttp->pinnedOptions('http://127.0.0.1/admin');
    }
}
