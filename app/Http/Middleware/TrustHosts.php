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
        return config('app.trusted_hosts');
    }
}
