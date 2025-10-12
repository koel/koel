<?php

namespace App\Services\Scanners\Contracts;

use Closure;
use DateInterval;
use DateTimeInterface;

interface ScannerCacheStrategy
{
    public function remember(
        string $key,
        Closure|DateTimeInterface|DateInterval|int|null $ttl,
        Closure $callback,
    ): mixed;
}
