<?php

namespace App\Services\Scanners\Contracts;

use Closure;

interface ScannerCacheStrategy
{
    public function remember(
        string $key,
        Closure $callback,
    ): mixed;
}
