<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as IlluminateTrustHost;
use Illuminate\Support\Str;

class TrustHosts extends IlluminateTrustHost
{
    private const string SUBDOMAIN_WILDCARD = '*.';

    /**
     * @return array<int, string>
     */
    public function hosts(): array
    {
        return collect(config('app.trusted_hosts'))
            ->map(static fn (string $host): string => trim($host))
            ->filter()
            ->map(self::patternFor(...))
            ->values()
            ->all();
    }

    private static function patternFor(string $host): string
    {
        if (Str::startsWith($host, self::SUBDOMAIN_WILDCARD)) {
            return '^.+\.' . preg_quote(Str::after($host, self::SUBDOMAIN_WILDCARD), '{') . '$';
        }

        return '^' . preg_quote($host, '{') . '$';
    }
}
