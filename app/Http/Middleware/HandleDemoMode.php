<?php

namespace App\Http\Middleware;

use App\Attributes\DisabledInDemo;
use Closure;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

class HandleDemoMode
{
    private static function ensureRequestIsAllowedInDemoMode(Request $request): void
    {
        $route = $request->route();
        $class = $route->getControllerClass();
        $method = $route->getActionMethod();

        $controllerReflection = new ReflectionClass($class);

        foreach ($controllerReflection->getAttributes(DisabledInDemo::class) as $attribute) {
            abort($attribute->newInstance()->code);
        }

        $methodReflection = new ReflectionMethod($class, $method);

        foreach ($methodReflection->getAttributes(DisabledInDemo::class) as $attribute) {
            abort($attribute->newInstance()->code);
        }
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('koel.misc.demo')) {
            self::ensureRequestIsAllowedInDemoMode($request);
        }

        return $next($request);
    }
}
