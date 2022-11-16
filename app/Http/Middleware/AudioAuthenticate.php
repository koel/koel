<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AudioAuthenticate
{
    public function handle(Request $request, Closure $next) // @phpcs:ignore
    {
        abort_unless($request->user()?->tokenCan('audio'), Response::HTTP_UNAUTHORIZED);

        return $next($request);
    }
}
