<?php

namespace App\Facades;

use App\Hooks\Filter;
use App\Hooks\FilterRegistry;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void listen(Filter $filter, Closure $callback)
 * @method static mixed filter(Filter $filter, mixed $payload)
 * @method static void forget(Filter $filter)
 *
 * @see \App\Hooks\FilterRegistry
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FilterRegistry::class;
    }
}
