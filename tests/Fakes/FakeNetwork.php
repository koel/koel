<?php

namespace Tests\Fakes;

use App\Services\Network\Network;
use IPLib\Factory;

/**
 * Skips DNS resolution so tests don't need real internet connectivity.
 * Format checks (scheme, host presence, literal-IP privacy) still run so any
 * test that exercises the SSRF guard against a known-private IP still works.
 */
class FakeNetwork extends Network
{
    /**
     * Return a synthetic public IP for any non-IP host that would otherwise require DNS.
     * IP literals are handed to the real implementation, which resolves them without
     * touching the network — so every literal the guard rejects is rejected here too.
     * The returned IP is never actually contacted in tests because Http::fake intercepts
     * before curl runs.
     *
     * @return list<string>
     */
    public function resolveToPublicIps(string $host): array
    {
        if ($host === '') {
            return [];
        }

        if (Factory::parseAddressString($host)) {
            return parent::resolveToPublicIps($host);
        }

        return ['203.0.113.1'];
    }
}
