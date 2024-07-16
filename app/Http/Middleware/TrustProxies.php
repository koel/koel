<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * Setting this to '*' will trust all proxies, as we don't know the IP address of the proxy server.
     *
     * @var array<string>
     */
    protected $proxies = ['*'];
}
