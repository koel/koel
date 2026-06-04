<?php

namespace App\Helpers;

use App\Exceptions\UnsafeUrlException;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;

/**
 * SSRF-hardened wrapper around Laravel's Http facade. Use it anywhere you fetch
 * a URL that came from outside your trust boundary. Both legs of the SSRF threat
 * model are closed for every request including each redirect hop:
 *
 *  - Redirect SSRF: every redirect target's host is re-resolved and re-validated.
 *  - DNS rebinding (TOCTOU): the resolved IPs are pinned into curl via
 *    CURLOPT_RESOLVE before each connect, so curl can't ask DNS again and get a
 *    different (private) IP.
 *
 * Both protections live in a single Guzzle middleware. Guzzle re-traverses the
 * handler stack for each redirect hop, so the middleware fires on the initial
 * request AND every redirect target — no manual redirect loop needed.
 *
 * Throws UnsafeUrlException whenever any request URL's host fails to resolve to
 * public IPs (initial URL or any redirect target).
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
        return $this->buildRequest($headers)->head($url);
    }

    /** Issue a GET request against the URL with full SSRF protection. */
    public function get(string $url, array $headers = []): Response
    {
        return $this->buildRequest($headers)->get($url);
    }

    /**
     * GET the URL as a stream (response body lazily read). Use for arbitrarily
     * large or unbounded payloads — e.g. live radio streams whose content type
     * we want to inspect without buffering the body.
     */
    public function getAsStream(string $url, array $headers = []): Response
    {
        return $this->buildRequest($headers, ['stream' => true])->get($url);
    }

    /**
     * Build a PSR-18 client whose handler stack applies the same hop-by-hop
     * pinning + validation as the Laravel Http methods above. Use for libraries
     * that take an injected client (e.g. PhanAn\Poddle\Poddle::fromUrl()).
     */
    public function getPinnedGuzzleClient(string $url, bool $trackRedirects = false, int $timeoutInSeconds = 30): Client
    {
        $allowRedirects = ['max' => 5];

        if ($trackRedirects) {
            $allowRedirects['track_redirects'] = true;
        }

        return new Client([
            'handler' => $this->buildHandlerStack(),
            'timeout' => $timeoutInSeconds,
            'allow_redirects' => $allowRedirects,
        ]);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $extraOptions
     */
    private function buildRequest(array $headers, array $extraOptions = []): PendingRequest
    {
        // withMiddleware pushes onto Laravel's existing handler stack — required
        // so Http::fake() in tests still intercepts. Setting `handler` via
        // withOptions would *replace* Laravel's stack and break the fake.
        $request = Http::withMiddleware($this->pinAndValidateMiddleware());

        if ($extraOptions !== []) {
            $request = $request->withOptions($extraOptions);
        }

        return $headers === [] ? $request : $request->withHeaders($headers);
    }

    /**
     * Guzzle handler stack with the pin-and-validate middleware. Used only by
     * getPinnedGuzzleClient (no Laravel Http::fake involvement there).
     */
    private function buildHandlerStack(): HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push($this->pinAndValidateMiddleware(), 'safe_http_pin');

        return $stack;
    }

    /**
     * Middleware that, for every outbound request: resolves the host's public
     * IPs, throws UnsafeUrlException if any are private/reserved or DNS fails,
     * and appends CURLOPT_RESOLVE entries to the transfer options so curl uses
     * the validated IPs instead of doing a fresh (rebindable) DNS lookup.
     */
    private function pinAndValidateMiddleware(): Closure
    {
        $network = $this->network;

        return static function (callable $next) use ($network): Closure {
            return static function (RequestInterface $request, array $options) use ($next, $network) {
                $uri = $request->getUri();
                $host = $uri->getHost();

                $ips = $network->resolveToPublicIps($host);

                throw_if(!$ips, UnsafeUrlException::forUrl((string) $uri));

                // IP literals don't trigger a DNS lookup, so nothing to pin.
                if (!filter_var($host, FILTER_VALIDATE_IP)) {
                    $port = $uri->getPort() ?? ($uri->getScheme() === 'https' ? 443 : 80);

                    $options['curl'] ??= [];
                    $options['curl'][CURLOPT_RESOLVE] = [
                        ...($options['curl'][CURLOPT_RESOLVE] ?? []),
                        ...array_map(static fn (string $ip): string => "{$host}:{$port}:{$ip}", $ips),
                    ];
                }

                return $next($request, $options);
            };
        };
    }
}
