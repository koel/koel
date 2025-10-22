<?php

namespace App\Services\Scanners;

use App\Services\Scanners\Contracts\ScannerCacheStrategy as ScannerCacheStrategyContract;
use Closure;
use Illuminate\Support\Collection;

class ScannerCacheStrategy implements ScannerCacheStrategyContract
{
    /** @var Collection<string, mixed> */
    private Collection $cache;

    public function __construct(private readonly int $maxCacheSize = 1000)
    {
        $this->cache = new Collection();
    }

    public function remember(string $key, Closure $callback): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        if ($this->cache->count() >= $this->maxCacheSize) {
            $this->cache->shift();
        }

        $result = $callback();
        $this->cache->put($key, $result);

        return $result;
    }
}
