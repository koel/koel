<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;

class ForceHttps
{
    public function __construct(
        private UrlGenerator $url,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (config('koel.force_https')) {
            $this->url->forceScheme('https');
        }

        return $next($request);
    }
}
