<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next) // @phpcs:ignore
    {
        if ($request->user()?->tokenCan('*')) {
            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson()) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        return redirect()->guest('/');
    }
}
