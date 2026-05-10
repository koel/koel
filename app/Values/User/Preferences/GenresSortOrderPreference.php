<?php

namespace App\Values\User\Preferences;

use Webmozart\Assert\Assert;

class GenresSortOrderPreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'asc';
    }

    public function assert(): void
    {
        Assert::oneOf(strtolower((string) $this->value), ['asc', 'desc']);
    }
}
