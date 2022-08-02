<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;

class ForceHttps
{
    public function __construct(private UrlGenerator $url)
    {
    }

    public function handle(Request $request, Closure $next) // @phpcs:ignore
    {
        if (config('koel.force_https')) {
            $this->url->forceScheme('https');
            $request->setTrustedProxies(
                [$request->getClientIp()],
                Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
            );
        }

        return $next($request);
    }
}
