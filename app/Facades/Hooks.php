<?php

namespace App\Facades;

use App\Hooks\HookRegistry;
use App\Values\HookHandle;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static HookHandle addAction(string $action, Closure $callback, int $priority = 10)
 * @method static void doAction(string $action, mixed ...$args)
 * @method static HookHandle addFilter(string $filter, Closure $callback, int $priority = 10)
 * @method static T applyFilters<T>(string $filter, T $value, mixed ...$args)
 * @method static void removeAction(HookHandle $handle)
 * @method static void removeFilter(HookHandle $handle)
 *
 * @see \App\Hooks\HookRegistry
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookRegistry::class;
    }
}
