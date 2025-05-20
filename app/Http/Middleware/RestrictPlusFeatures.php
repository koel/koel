<?php

namespace App\Http\Middleware;

use App\Attributes\RequiresPlus;
use App\Facades\License;
use App\Http\Middleware\Concerns\ChecksControllerAttributes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use ReflectionAttribute;
use Symfony\Component\HttpFoundation\Response;

class RestrictPlusFeatures
{
    use ChecksControllerAttributes;

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (License::isCommunity()) {
            optional(
                Arr::get(self::getAttributeUsageFromRequest($request, RequiresPlus::class), 0),
                static fn (ReflectionAttribute $attribute) => abort($attribute->newInstance()->code)
            );
        }

        return $next($request);
    }
}
