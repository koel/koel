<?php

namespace App\Pipelines\Encyclopedia;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

trait TriesRemember
{
    /**
     * `Cache::has()` reports a stored null as a miss, so a lookup that found nothing would be repeated on
     * every visit. It is stored as this instead, and remembered only for a while: MusicBrainz may well
     * have the entry next month.
     */
    private const bool NOTHING_FOUND = false;
    private const int NOTHING_FOUND_TTL_IN_DAYS = 7;

    private function tryRemember(string $key, DateTimeInterface|int $ttl, Closure $callback): mixed
    {
        return $this->tryRememberFor($key, $ttl, $callback);
    }

    private function tryRememberForever(string $key, Closure $callback): mixed
    {
        return $this->tryRememberFor($key, null, $callback);
    }

    private function tryRememberFor(string $key, DateTimeInterface|int|null $ttl, Closure $callback): mixed
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
