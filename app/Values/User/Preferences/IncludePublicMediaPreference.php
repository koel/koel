<?php

namespace App\Values\User\Preferences;

class IncludePublicMediaPreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
