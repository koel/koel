<?php

namespace App\Http\Middleware;

use App\Helpers\Uuid;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tag the request so that everything it logs — including from queued jobs it dispatches,
 * which are logged long after the response has been sent — can be traced back to it.
 */
class AddRequestContextForLogging
{
    public function handle(Request $request, Closure $next): Response
    {
        Context::add([
            'request_id' => Uuid::generate(),
            // Asking for the user here resolves the guard early, before the auth middleware runs.
            'user_id' => $request->user()?->id,
        ]);

        return $next($request);
    }
}
