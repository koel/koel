<?php

namespace Tests\Concerns;

use PHPUnit\Framework\Assert;

trait AssertsArraySubset
{
    public static function assertArraySubset(array $subset, array $set, ?string $message = null): void
    {
        if (self::isArraySubset($subset, $set)) {
            Assert::assertTrue(true); // @phpstan-ignore-line
        } else {
            Assert::fail($message ?? 'Failed asserting that the array is a subset of another array');
        }
    }

    private static function isArraySubset(array $subset, array $set): bool
    {
        foreach ($subset as $key => $value) {
            if (array_key_exists($key, $set)) {
                if (is_array($value)) {
                    if (!is_array($set[$key]) || !self::isArraySubset($value, $set[$key])) {
                        return false;
                    }
                } elseif ($set[$key] !== $value) {
                    return false;
                }
            } else {
                if (is_numeric($key)) {
                    if (!in_array($value, $set, true)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        return true;
    }
}
