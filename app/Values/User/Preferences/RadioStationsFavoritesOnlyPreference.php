<?php

namespace App\Values\User\Preferences;

class RadioStationsFavoritesOnlyPreference extends Preference
{
    public function getDefaultValue(): false
    {
        return false;
    }

    protected function cast(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
