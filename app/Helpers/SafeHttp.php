<?php

namespace App\Helpers;

use App\Exceptions\UnsafeUrlException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Defenses for SSRF on outbound HTTP. Every call site that follows redirects
 * needs per-hop validation: an attacker-controlled URL can pass the initial
 * SafeUrl check, then 302 to 127.0.0.1, 169.254.169.254, etc.
 *
 * Consumers using Laravel Http facade: `Http::withOptions(SafeHttp::redirectOptions())`.
 * Consumers needing a PSR-18 client (e.g. Poddle): `SafeHttp::guzzleClient()`.
 *
 * Note: this addresses redirect-SSRF only. DNS rebinding via TOCTOU between
 * the validator's DNS lookup and the HTTP client's connect-time lookup is a
 * separate concern that requires IP pinning (e.g. curl's CURLOPT_RESOLVE) and
 * is tracked separately.
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
        $network = $this->network;

        return [
            'allow_redirects' => [
                'max' => $max,
                'on_redirect' => static function (
                    RequestInterface $request,
                    ResponseInterface $response,
                    UriInterface $uri,
                ) use ($network): void {
                    if (!$network->isSafeUrl((string) $uri)) {
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
        $network = $this->network;

        $stack = HandlerStack::create();

        $stack->push(Middleware::mapRequest(static function (RequestInterface $request) use (
            $network,
        ): RequestInterface {
            if (!$network->isSafeUrl((string) $request->getUri())) {
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
}
