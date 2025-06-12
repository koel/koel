<?php

namespace App\Helpers;

abstract class TestableIdentifier
{
    protected static ?string $frozenValue = null;

    abstract public static function generate(): string;

    abstract public static function freeze(?string $value = null): string;

    public static function unfreeze(): void
    {
        self::$frozenValue = null;
    }
}
