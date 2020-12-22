<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    protected $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->auth->guest()) {
            if ($request->ajax() || $request->route()->getName() === 'play') {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest('/');
            }
        }

        return $next($request);
    }
}
