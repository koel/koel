<?php

namespace App\Services\Scanners;

use App\Services\Scanners\Contracts\ScannerCacheStrategy;
use Closure;
use DateInterval;
use DateTimeInterface;

class ScannerNoCacheStrategy implements ScannerCacheStrategy
{
    public function remember(
        string $key,
        DateInterval|DateTimeInterface|int|Closure|null $ttl,
        Closure $callback,
    ): mixed {
        return $callback();
    }
}
