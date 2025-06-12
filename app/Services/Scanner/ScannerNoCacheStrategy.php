<?php

namespace App\Services\Scanner;

use App\Services\Scanner\Contracts\ScannerCacheStrategy;
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
