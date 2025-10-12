<?php

namespace App\Services\Scanners;

use App\Services\Scanners\Contracts\ScannerCacheStrategy as ScannerCacheStrategyContract;
use Closure;
use Illuminate\Support\Collection;

class ScannerCacheStrategy implements ScannerCacheStrategyContract
{
    private int $maxCacheSize;

    /** @var Collection<string, mixed> */
    private Collection $cache;

    public function __construct(int $maxCacheSize = 1000)
    {
        $this->maxCacheSize = $maxCacheSize;
        $this->cache = new Collection();
    }

    public function remember(string $key, Closure $callback): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache[$key];
        }

        if ($this->cache->count() >= $this->maxCacheSize) {
            $this->cache->shift();
        }

        $this->cache[$key] = $callback();

        return $this->cache[$key];
    }
}
