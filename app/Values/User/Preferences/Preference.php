<?php

namespace App\Values\User\Preferences;

use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use Throwable;

/**
 * @property mixed $value
 */
abstract class Preference
{
    private mixed $resolvedValue = null;

    final public function __construct() {}

    public static function make(mixed $value): static
    {
        $instance = new static();
        $instance->value = $value === null ? $instance->getDefaultValue() : $instance->cast($value);

        return $instance;
    }

    public function __set(string $name, mixed $value): void
    {
        if ($name !== 'value') {
            throw new InvalidArgumentException("Unknown property: {$name}");
        }

        $previous = $this->resolvedValue;
        $this->resolvedValue = $value;

        try {
            $this->assert();
        } catch (Throwable $exception) {
            $this->resolvedValue = $previous;
            throw $exception;
        }
    }

    public function __get(string $name): mixed
    {
        if ($name !== 'value') {
            throw new InvalidArgumentException("Unknown property: {$name}");
        }

        return $this->resolvedValue;
    }

    public function getKey(): string
    {
        $shortName = (new ReflectionClass(static::class))->getShortName();

        return Str::snake(Str::beforeLast($shortName, 'Preference'));
    }

    public function getProperty(): string
    {
        return Str::camel($this->getKey());
    }

    public function getDefaultValue(): mixed
    {
        return null;
    }

    public function getValue(): mixed
    {
        return $this->resolvedValue;
    }

    public function assert(): void {}

    public function isCustomizable(): bool
    {
        return true;
    }

    /** @return list<string> Legacy storage keys to read as a backwards-compat fallback. */
    public function getAliases(): array
    {
        return [];
    }

    protected function cast(mixed $value): mixed
    {
        return $value;
    }
}
