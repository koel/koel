<?php

namespace App\Facades;

use App\Enums\Hooks\Action;
use App\Enums\Hooks\Filter;
use App\Hooks\HookRegistry;
use App\Values\HookHandle;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static HookHandle addAction(Action|string $action, Closure $callback, int $priority = 10)
 * @method static void doAction(Action|string $action, mixed ...$args)
 * @method static HookHandle addFilter(Filter|string $filter, Closure $callback, int $priority = 10)
 * @method static T applyFilters<T>(Filter|string $filter, T $value, mixed ...$args)
 * @method static void remove(HookHandle $handle)
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
