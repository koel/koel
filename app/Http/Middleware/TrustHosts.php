<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as IlluminateTrustHost;

class TrustHosts extends IlluminateTrustHost
{
    /**
     * @return array<int, string>
     */
    public function hosts(): array
    {
        return collect(config('app.trusted_hosts'))
            ->filter()
            ->map(static fn (string $host): string => '^' . preg_quote(trim($host), '{') . '$')
            ->values()
            ->all();
    }
}
