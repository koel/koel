<?php

namespace App\Values\User\Preferences;

class TranscodeOnMobilePreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
