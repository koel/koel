<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Authenticate requests from Object Storage services (like S3).
 * Such requests must have an apKey data, which matches with our app key.
 */
class ObjectStorageAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->appKey !== config('app.key')) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
