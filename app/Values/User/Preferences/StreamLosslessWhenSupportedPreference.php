<?php

namespace App\Values\User\Preferences;

class StreamLosslessWhenSupportedPreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
