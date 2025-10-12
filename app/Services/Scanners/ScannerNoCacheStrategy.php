<?php

namespace App\Services\Scanners;

use App\Services\Scanners\Contracts\ScannerCacheStrategy;
use Closure;

class ScannerNoCacheStrategy implements ScannerCacheStrategy
{
    public function remember(
        string $key,
        Closure $callback,
    ): mixed {
        return $callback();
    }
}
