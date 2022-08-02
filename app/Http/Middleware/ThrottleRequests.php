<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests as BaseThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests extends BaseThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 300, $decayMinutes = 1, $prefix = ''): Response
    {
        if (app()->environment('production')) {
            return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
        }

        return $next($request);
    }
}
