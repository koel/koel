<?php

namespace App\Values\User\Preferences;

class ShowWaveformPreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
