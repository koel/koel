<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Uuid extends TestableIdentifier
{
    public const REGEX = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    public static function generate(): string
    {
        return self::$frozenValue ?: Str::uuid()->toString();
    }

    public static function freeze(?string $value = null): string
    {
        self::$frozenValue = $value ?? Str::uuid()->toString();

        return self::$frozenValue;
    }
}
