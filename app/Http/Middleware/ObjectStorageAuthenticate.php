<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Authenticate requests from Object Storage services (like S3).
 * Such requests must have an `appKey` parameter (query string or request body), which matches our app key.
 */
class ObjectStorageAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->input('appKey') === config('app.key'), Response::HTTP_UNAUTHORIZED);

        return $next($request);
    }
}
