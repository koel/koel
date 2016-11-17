<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Check if the app is running in an E2E session and use the proper data settings.
 */
class UseDifferentConfigIfE2E
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (array_get($_SERVER, 'SERVER_PORT') === '8081') {
            config(['database.default' => 'sqlite-e2e']);
        }

        return $next($request);
    }
}
