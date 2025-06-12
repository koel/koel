<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Ulid extends TestableIdentifier
{
    public static function generate(): string
    {
        return self::$frozenValue ?: Str::lower(Str::ulid());
    }

    public static function freeze(?string $value = null): string
    {
        self::$frozenValue = $value ?? Str::lower(Str::ulid());

        return self::$frozenValue;
    }
}
