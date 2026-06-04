<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureEmbedsEnabled
{
    public function __construct(
        #[Config('koel.embed.enabled')]
        private readonly bool $enabled,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->enabled) {
            throw new NotFoundHttpException();
        }

        return $next($request);
    }
}
