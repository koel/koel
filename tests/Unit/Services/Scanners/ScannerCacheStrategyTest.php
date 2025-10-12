<?php

namespace Tests\Unit\Services\Scanners;

use App\Services\Scanners\ScannerCacheStrategy;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ScannerCacheStrategyTest extends TestCase
{
    private ScannerCacheStrategy $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->cache = new ScannerCacheStrategy(3);
    }

    #[Test]
    public function rememberCachesValueDoesNotRecomputeOnSubsequentCalls(): void
    {
        $counter = 0;
        $callback = static function () use (&$counter): int {
            $counter++;
            return 42;
        };

        $first = $this->cache->remember('key', $callback);
        $second = $this->cache->remember('key', $callback);

        self::assertSame(42, $first);
        self::assertSame(42, $second);
        self::assertSame(1, $counter, 'Callback should be executed only once for the same key.');
    }

    #[Test]
    public function differentKeysAreCachedIndependently(): void
    {
        $a = $this->cache->remember('a', static fn () => 'foo');
        $b = $this->cache->remember('b', static fn () => 'bar');

        self::assertSame('foo', $a);
        self::assertSame('bar', $b);

        $a2 = $this->cache->remember('a', static fn () => 'baz');
        $b2 = $this->cache->remember('b', static fn () => 'qux');

        self::assertSame('foo', $a2);
        self::assertSame('bar', $b2);
    }

    #[Test]
    public function evictsOldestEntryAfterCacheExceedsMaxSize(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $key = 'k' . $i;
            $val = $i;
            $this->cache->remember($key, static fn () => $val);
        }

        // Last entry before eviction
        $valueForK1 = $this->cache->remember('k1', static function (): void {
            throw new RuntimeException('Callback for k1 should not be executed before eviction.');
        });
        self::assertSame(1, $valueForK1);

        //Trigger eviction
        $this->cache->remember('k4', static fn () => 4);

        $new = $this->cache->remember('k1', static fn () => 9999);
        self::assertSame(9999, $new);
    }
}
