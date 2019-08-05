<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;

class ForceHttps
{
    private $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    public function handle(Request $request, Closure $next)
    {
        if (config('koel.force_https')) {
            $this->url->forceScheme('https');
            $request->setTrustedProxies([$request->getClientIp()], Request::HEADER_X_FORWARDED_ALL);
        }

        return $next($request);
    }
}
