<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class Authenticate
{
    public function __construct(protected Guard $auth)
    {
    }

    public function handle(Request $request, Closure $next) // @phpcs:ignore
    {
        if ($this->auth->guest()) {
            if ($request->ajax() || $request->wantsJson() || $request->route()->getName() === 'play') {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest('/');
            }
        }

        return $next($request);
    }
}
