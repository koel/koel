<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\Request;
use ReflectionAttribute;
use ReflectionClass;
use Throwable;

trait ChecksControllerAttributes
{
    /** @return array<ReflectionAttribute> */
    private static function getAttributeUsageFromRequest(Request $request, string $attributeClass): ?array
    {
        try {
            $route = $request->route();

            [$controller, $method] = explode('@', $route->getAction('uses'));

            $classReflection = new ReflectionClass($controller);
            $methodReflection = $classReflection->getMethod($method);

            return $methodReflection->getAttributes($attributeClass)
                ?: $classReflection->getAttributes($attributeClass);
        } catch (Throwable) {
            return [];
        }
    }
}
