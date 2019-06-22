<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (config('koel.force_https')) {
            URL::forceScheme('https');
            $request->setTrustedProxies([$request->getClientIp()], Request::HEADER_X_FORWARDED_ALL);
        }

        return $next($request);
    }
}
