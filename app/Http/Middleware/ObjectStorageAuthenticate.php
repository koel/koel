<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Authenticate requests from Object Storage services (like S3).
 * Such requests must have an `appKey` query parameter, which matches our app key.
 */
class ObjectStorageAuthenticate
{
    /** @return mixed */
    public function handle(Request $request, Closure $next)
    {
        if ($request->appKey !== config('app.key')) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
