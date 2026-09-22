<?php

namespace App\Facades;

use App\Hooks\Action;
use App\Hooks\Filter;
use App\Hooks\HookRegistry;
use App\Values\HookHandle;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static HookHandle addAction(Action $action, Closure $callback, int $priority = 10)
 * @method static void doAction(Action $action, mixed ...$args)
 * @method static HookHandle addFilter(Filter $filter, Closure $callback, int $priority = 10)
 * @method static mixed applyFilters(Filter $filter, mixed $value, mixed ...$args)
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
