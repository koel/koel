<?php

namespace App\Pipelines\Encyclopedia;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

trait TriesRemember
{
    /** `Cache::has()` reports a stored null as a miss, so nothing-found is stored as this instead. */
    private const string NOTHING_FOUND = '__koel_nothing_found__';

    private static function tryRemember(
        string $key,
        DateTimeInterface|int $ttl,
        DateTimeInterface|int $nothingFoundTtl,
        Closure $callback,
    ): mixed {
        return self::tryRememberFor($key, $ttl, $nothingFoundTtl, $callback);
    }

    private static function tryRememberForever(
        string $key,
        DateTimeInterface|int $nothingFoundTtl,
        Closure $callback,
    ): mixed {
        return self::tryRememberFor($key, null, $nothingFoundTtl, $callback);
    }

    private static function tryRememberFor(
        string $key,
        DateTimeInterface|int|null $ttl,
        DateTimeInterface|int $nothingFoundTtl,
        Closure $callback,
    ): mixed {
        if (Cache::has($key)) {
            $cached = Cache::get($key);

            return $cached === self::NOTHING_FOUND ? null : $cached;
        }

        return rescue(static function () use ($key, $ttl, $nothingFoundTtl, $callback): mixed {
            $value = $callback();

            if ($value === null) {
                Cache::put($key, self::NOTHING_FOUND, $nothingFoundTtl);

                return null;
            }

            if ($ttl === null) {
                Cache::forever($key, $value);
            } else {
                Cache::put($key, $value, $ttl);
            }

            return $value;
        });
    }
}
