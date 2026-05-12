<?php

namespace App\Values\User\Preferences;

class ContinuousPlaybackPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
