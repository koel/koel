<?php

namespace App\Http\Middleware;

use App\Services\BuildIdentifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddBuildHeader
{
    public function __construct(
        private readonly BuildIdentifier $buildIdentifier,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $build = $this->buildIdentifier->getId();

        if ($build) {
            $response->headers->set('X-Koel-Build', $build);
        }

        return $response;
    }
}
