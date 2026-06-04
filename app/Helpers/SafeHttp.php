<?php

namespace App\Helpers;

use App\Exceptions\UnsafeUrlException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * SSRF-hardened wrapper around Laravel's Http facade. Use it anywhere you fetch
 * a URL that came from outside your trust boundary. Both legs of the SSRF threat
 * model are closed:
 *
 *  - Redirect SSRF: every redirect hop is re-validated against Network::isSafeUrl.
 *  - DNS rebinding (TOCTOU): DNS is resolved once at validation time and the
 *    resolved IPs are pinned into curl via CURLOPT_RESOLVE, so the connect-time
 *    DNS lookup can't return a different (private) IP.
 *
 * Throws UnsafeUrlException at validation time (initial URL not public, or DNS
 * resolution fails) and at request time (any redirect hop points to a non-public
 * target). Callers that want a typed boundary should catch UnsafeUrlException.
 *
 * For PSR-18 consumers (e.g. PhanAn\Poddle\Poddle::fromUrl()), use
 * SafeHttp::getPinnedGuzzleClient($url) instead.
 */
class SafeHttp
{
    public function __construct(
        private readonly Network $network,
    ) {}

    /** Issue a HEAD request against the URL with full SSRF protection. */
    public function head(string $url, array $headers = []): Response
    {
        return $this->buildRequest($url, $headers)->head($url);
    }

    /** Issue a GET request against the URL with full SSRF protection. */
    public function get(string $url, array $headers = []): Response
    {
        return $this->buildRequest($url, $headers)->get($url);
    }

    /**
     * GET the URL as a stream (response body lazily read). Use for arbitrarily
     * large or unbounded payloads — e.g. live radio streams whose content type
     * we want to inspect without buffering the body.
     */
    public function getAsStream(string $url, array $headers = []): Response
    {
        return $this->buildRequest($url, $headers, ['stream' => true])->get($url);
    }

    /**
     * Build a PSR-18 client pinned to the resolved IPs of `$url`'s host. Use for
     * libraries that take an injected client AND target a known URL (e.g.
     * PhanAn\Poddle\Poddle::fromUrl($url, ..., $client)).
     */
    public function getPinnedGuzzleClient(string $url, bool $trackRedirects = false, int $timeoutInSeconds = 30): Client
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            if (!$this->network->isSafeUrl((string) $request->getUri())) {
                throw UnsafeUrlException::forUrl((string) $request->getUri());
            }

            return $request;
        }));

        $options = $this->buildPinnedOptions($url);

        if ($trackRedirects) {
            $options['allow_redirects']['track_redirects'] = true;
        }

        return new Client([
            'handler' => $stack,
            'timeout' => $timeoutInSeconds,
            ...$options,
        ]);
    }

    /**
     * Returns Http facade options that pin the URL's host to its currently-resolved
     * public IPs and re-validate each redirect hop. Exposed for granular tests and
     * for the rare caller that needs to compose with extra Http options; production
     * code should prefer head() / get() / getAsStream().
     *
     * @internal
     * @return array<string, mixed>
     */
    public function buildPinnedOptions(string $url, int $max = 5): array
    {
        [$host, $port] = self::extractHostAndPort($url);

        $ips = $this->network->resolveToPublicIps($host);

        throw_if($ips === null, UnsafeUrlException::forUrl($url));

        $options = $this->buildRedirectOptions($max);

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // No DNS lookup happens for IP literals, so there's nothing to pin.
            return $options;
        }

        $options['curl'] = [
            CURLOPT_RESOLVE => array_map(static fn (string $ip) => "{$host}:{$port}:{$ip}", $ips),
        ];

        return $options;
    }

    /**
     * Returns the allow_redirects options that re-validate every redirect target
     * via Network::isSafeUrl, throwing UnsafeUrlException on a private or reserved
     * target.
     *
     * @return array<string, mixed>
     */
    private function buildRedirectOptions(int $maxRedirects = 5): array
    {
        return [
            'allow_redirects' => [
                'max' => $maxRedirects,
                'on_redirect' => function (
                    RequestInterface $request,
                    ResponseInterface $response,
                    UriInterface $uri,
                ): void {
                    if (!$this->network->isSafeUrl((string) $uri)) {
                        throw UnsafeUrlException::forUrl((string) $uri);
                    }
                },
            ],
        ];
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $extraOptions
     */
    private function buildRequest(string $url, array $headers, array $extraOptions = []): PendingRequest
    {
        $request = Http::withOptions([...$this->buildPinnedOptions($url), ...$extraOptions]);

        return $headers === [] ? $request : $request->withHeaders($headers);
    }

    /** @return array{0: string, 1: int} */
    private static function extractHostAndPort(string $url): array
    {
        $uri = Uri::of($url);
        $host = $uri->host();
        $port = $uri->port() ?? ($uri->scheme() === 'https' ? 443 : 80);

        return [$host, $port];
    }
}
