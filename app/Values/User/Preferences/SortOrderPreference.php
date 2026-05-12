<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

abstract class SortOrderPreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'asc';
    }

    public function assert(): void
    {
        Assert::oneOf($this->value, ['asc', 'desc']);
    }

    protected function cast(mixed $value): string
    {
        return strtolower((string) $value);
    }
}
