<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Authenticate requests from Object Storage services (like S3).
 * Such requests must have an `appKey` query parameter, which matches our app key.
 */
class ObjectStorageAuthenticate
{
    public function handle(Request $request, Closure $next) // @phpcs:ignore
    {
        abort_unless($request->get('appKey') === config('app.key'), Response::HTTP_UNAUTHORIZED);

        return $next($request);
    }
}
