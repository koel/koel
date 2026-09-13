<?php

namespace App\Pipelines\Encyclopedia;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

trait TriesRemember
{
    /** `Cache::has()` reports a stored null as a miss, so nothing-found is stored as this instead. */
    private const string NOTHING_FOUND = '__koel_nothing_found__';
    private const int NOTHING_FOUND_TTL_IN_DAYS = 7;

    private static function tryRemember(string $key, DateTimeInterface|int $ttl, Closure $callback): mixed
    {
        return self::tryRememberFor($key, $ttl, $callback);
    }

    private static function tryRememberForever(string $key, Closure $callback): mixed
    {
        return self::tryRememberFor($key, null, $callback);
    }

    private static function tryRememberFor(string $key, DateTimeInterface|int|null $ttl, Closure $callback): mixed
    {
        if (Cache::has($key)) {
            $cached = Cache::get($key);

            return $cached === self::NOTHING_FOUND ? null : $cached;
        }

        return rescue(static function () use ($key, $ttl, $callback): mixed {
            $value = $callback();

            if ($value === null) {
                Cache::put($key, self::NOTHING_FOUND, now()->addDays(self::NOTHING_FOUND_TTL_IN_DAYS));

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
