<?php

namespace App\Values\User\Preferences;

class RadioStationsFavoritesOnlyPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
