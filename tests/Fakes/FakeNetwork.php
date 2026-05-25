<?php

namespace Tests\Fakes;

use App\Helpers\Network;
use Illuminate\Support\Uri;
use Throwable;

/**
 * Skips DNS resolution so tests don't need real internet connectivity.
 * Format checks (scheme, host presence, literal-IP privacy) still run so any
 * test that exercises the SSRF guard against a known-private IP still works.
 */
class FakeNetwork extends Network
{
    private const array SAFE_SCHEMES = ['http', 'https'];

    public function isSafeUrl(string $url): bool
    {
        try {
            $uri = Uri::of($url);
        } catch (Throwable) {
            return false;
        }

        if (!in_array($uri->scheme(), self::SAFE_SCHEMES, true)) {
            return false;
        }

        $host = $uri->host();

        return $host !== '' && $this->isPublicHost($host);
    }

    public function isPublicHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (
                filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            );
        }

        return $host !== '';
    }
}
