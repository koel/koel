<?php

namespace App\Facades;

use App\Hooks\Hook;
use App\Hooks\HookRegistry;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void listen(Hook $hook, Closure $callback)
 * @method static mixed filter(Hook $hook, mixed $payload)
 * @method static void forget(Hook $hook)
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
