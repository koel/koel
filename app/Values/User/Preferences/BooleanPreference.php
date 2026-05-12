<?php

namespace App\Values\User\Preferences;

abstract class BooleanPreference extends Preference
{
    protected function cast(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
