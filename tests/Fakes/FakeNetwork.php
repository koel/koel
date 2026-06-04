<?php

namespace Tests\Fakes;

use App\Helpers\Network;

/**
 * Skips DNS resolution so tests don't need real internet connectivity.
 * Format checks (scheme, host presence, literal-IP privacy) still run so any
 * test that exercises the SSRF guard against a known-private IP still works.
 */
class FakeNetwork extends Network
{
    /**
     * Return a synthetic public IP for any non-IP host that would otherwise
     * require DNS, and apply the literal-IP privacy check inline (no recursion
     * back into isPublicHost). The pinned IP is never actually contacted in
     * tests because Http::fake intercepts before curl runs.
     *
     * @return list<string>
     */
    public function resolveToPublicIps(string $host): array
    {
        if ($host === '') {
            return [];
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (
                filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                    ? [$host]
                    : []
            );
        }

        return ['203.0.113.1'];
    }
}
