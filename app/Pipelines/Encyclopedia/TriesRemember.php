<?php

namespace App\Pipelines\Encyclopedia;

use App\Exceptions\MusicBrainzBusyException;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Throwable;

trait TriesRemember
{
    /** `self::encyclopediaStore()->has()` reports a stored null as a miss, so nothing-found is stored as this instead. */
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
        if (self::encyclopediaStore()->has($key)) {
            $cached = self::encyclopediaStore()->get($key);

            return $cached === self::NOTHING_FOUND ? null : $cached;
        }

        return rescue(
            static function () use ($key, $ttl, $nothingFoundTtl, $callback): mixed {
                $value = $callback();

                if ($value === null) {
                    self::encyclopediaStore()->put($key, self::NOTHING_FOUND, $nothingFoundTtl);

                    return null;
                }

                if ($ttl === null) {
                    self::encyclopediaStore()->forever($key, $value);
                } else {
                    self::encyclopediaStore()->put($key, $value, $ttl);
                }

                return $value;
            },
            static fn (Throwable $e) => $e instanceof MusicBrainzBusyException ? throw $e : null,
            report: static fn (Throwable $e): bool => !$e instanceof MusicBrainzBusyException,
        );
    }

    private static function encyclopediaStore(): Repository
    {
        return Cache::store('encyclopedia');
    }
}
