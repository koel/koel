<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GetUserFromToken extends BaseMiddleware
{
    /**
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return $this->respond('tymon.jwt.absent', 'token_not_provided', 401);
        }

        $user = $this->auth->authenticate($token);

        if (!$user) {
            return $this->respond('tymon.jwt.user_not_found', 'user_not_found', 401);
        }

        $this->events->dispatch('tymon.jwt.valid', $user);

        return $next($request);
    }
}
