<?php

namespace App\Values\User\Preferences;

class NormalizeVolumePreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
