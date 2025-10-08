<?php

namespace App\Pipelines\Encyclopedia;

use Closure;
use Illuminate\Support\Facades\Cache;

trait RemembersForever
{
    private function tryRememberForever(string $key, Closure $callback): mixed
    {
        $value = Cache::get($key);

        if ($value) {
            return $value;
        }

        return rescue(static fn () => Cache::rememberForever($key, $callback));
    }
}
