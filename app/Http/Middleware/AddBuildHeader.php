<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddBuildHeader
{
    public function __construct(
        private readonly Vite $vite,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $build = $this->vite->manifestHash();

        if ($build) {
            $response->headers->set('X-Koel-Build', $build);
        }

        return $response;
    }
}
