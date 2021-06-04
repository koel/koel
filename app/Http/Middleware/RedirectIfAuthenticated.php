<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class RedirectIfAuthenticated
{
    protected Guard $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /** @return mixed */
    public function handle(Request $request, Closure $next) // @phpcs:ignore
    {
        if ($this->auth->check()) {
            return redirect('/');
        }

        return $next($request);
    }
}
