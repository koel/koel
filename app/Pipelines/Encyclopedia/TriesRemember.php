<?php

namespace App\Pipelines\Encyclopedia;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

trait TriesRemember
{
    private function tryRemember(string $key, DateTimeInterface|int $ttl, Closure $callback): mixed
    {
        return Cache::has($key)
            ? Cache::get($key)
            : rescue(static fn () => Cache::remember($key, $ttl, $callback));
    }

    private function tryRememberForever(string $key, Closure $callback): mixed
    {
        return Cache::has($key)
            ? Cache::get($key)
            : rescue(static fn () => Cache::rememberForever($key, $callback));
    }
}
