<?php

namespace App\Helpers;

abstract class TestableIdentifier
{
    protected static ?string $frozenValue = null;

    abstract protected static function newIdentifier(): string;

    public static function generate(): string
    {
        return static::$frozenValue ?: static::newIdentifier();
    }

    /**
     * Freeze the identifier value for testing purposes.
     *
     * @param ?string $value A value to freeze, or null to generate a new one.
     */
    public static function freeze(?string $value = null): string
    {
        static::$frozenValue = $value ?? static::newIdentifier();

        return static::$frozenValue;
    }

    public static function unfreeze(): void
    {
        static::$frozenValue = null;
    }
}
