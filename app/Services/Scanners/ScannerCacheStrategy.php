<?php

namespace App\Services\Scanners;

use App\Services\Scanners\Contracts\ScannerCacheStrategy as ScannerCacheStrategyContract;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

class ScannerCacheStrategy implements ScannerCacheStrategyContract
{
    public function remember(
        string $key,
        DateInterval|DateTimeInterface|int|Closure|null $ttl,
        Closure $callback,
    ): mixed {
        return Cache::remember($key, $ttl, $callback);
    }
}
