<?php

namespace App\Http\Middleware;

use App\Attributes\DisabledInDemo;
use App\Http\Middleware\Concerns\ChecksControllerAttributes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use ReflectionAttribute;
use Symfony\Component\HttpFoundation\Response;

class HandleDemoMode
{
    use ChecksControllerAttributes;

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('koel.misc.demo')) {
            optional(
                Arr::get(self::getAttributeUsageFromRequest($request, DisabledInDemo::class), 0),
                static fn (ReflectionAttribute $attribute) => abort($attribute->newInstance()->code)
            );
        }

        return $next($request);
    }
}
