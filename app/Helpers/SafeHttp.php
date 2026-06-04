<?php

namespace App\Helpers;

use App\Exceptions\UnsafeUrlException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Support\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Defenses for SSRF on outbound HTTP. Closes both legs of the SSRF threat model:
 *
 *  - Redirect SSRF: per-hop validation via on_redirect / mapRequest middleware.
 *  - DNS rebinding (TOCTOU): IP pinning via CURLOPT_RESOLVE — DNS is resolved
 *    once at validation time and the resolved IPs are bound into curl, so an
 *    attacker can't flip the DNS record between validation and connect.
 *
 * Consumers using Laravel Http facade: `Http::withOptions(SafeHttp::pinnedOptions($url))`.
 * Consumers needing a PSR-18 client (e.g. Poddle): `SafeHttp::pinnedGuzzleClient($url)`.
 */
class SafeHttp
{
    public function __construct(
        private readonly Network $network,
    ) {}

    /**
     * Returns the `allow_redirects` options that re-validate every redirect
     * target via Network::isSafeUrl, throwing UnsafeUrlException on a private
     * or reserved target.
     *
     * @return array<string, mixed>
     */
    public function redirectOptions(int $max = 5): array
    {
        return [
            'allow_redirects' => [
                'max' => $max,
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
     * Build a PSR-18 client that applies the same per-redirect-hop validation
     * via Guzzle middleware. Use for libraries that take an injected client
     * (e.g. PhanAn\Poddle\Poddle::fromUrl()).
     */
    public function guzzleClient(int $timeoutInSeconds = 30): Client
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            if (!$this->network->isSafeUrl((string) $request->getUri())) {
                throw UnsafeUrlException::forUrl((string) $request->getUri());
            }

            return $request;
        }));

        return new Client([
            'handler' => $stack,
            'timeout' => $timeoutInSeconds,
            ...$this->redirectOptions(),
        ]);
    }

    /**
     * Returns Http facade options with both redirect validation and DNS-rebinding
     * protection: the given URL's host is resolved to its public IPs at this
     * moment and pinned into curl via CURLOPT_RESOLVE, so the connect-time DNS
     * resolution can't return a different (private) IP.
     *
     * Throws UnsafeUrlException if any resolved IP is private/reserved or the
     * host fails to resolve.
     *
     * @return array<string, mixed>
     */
    public function pinnedOptions(string $url, int $max = 5): array
    {
        [$host, $port] = self::extractHostPort($url);

        $ips = $this->network->resolveToPublicIps($host);

        if ($ips === null) {
            throw UnsafeUrlException::forUrl($url);
        }

        $options = $this->redirectOptions($max);

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // No DNS lookup happens for IP literals, so there's nothing to pin.
            return $options;
        }

        $options['curl'] = [
            CURLOPT_RESOLVE => array_map(static fn (string $ip): string => "{$host}:{$port}:{$ip}", $ips),
        ];

        return $options;
    }

    /**
     * Build a PSR-18 client pinned to the resolved IPs of `$url`'s host. Use for
     * libraries that take an injected client AND target a known URL (e.g.
     * PhanAn\Poddle\Poddle::fromUrl($url, ..., $client)).
     */
    public function pinnedGuzzleClient(string $url, int $timeoutInSeconds = 30): Client
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            if (!$this->network->isSafeUrl((string) $request->getUri())) {
                throw UnsafeUrlException::forUrl((string) $request->getUri());
            }

            return $request;
        }));

        return new Client([
            'handler' => $stack,
            'timeout' => $timeoutInSeconds,
            ...$this->pinnedOptions($url),
        ]);
    }

    /** @return array{0: string, 1: int} */
    private static function extractHostPort(string $url): array
    {
        $uri = Uri::of($url);
        $host = $uri->host();
        $port = $uri->port() ?? ($uri->scheme() === 'https' ? 443 : 80);

        return [$host, $port];
    }
}
