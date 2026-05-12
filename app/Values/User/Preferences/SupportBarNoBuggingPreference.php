<?php

namespace App\Values\User\Preferences;

class SupportBarNoBuggingPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
