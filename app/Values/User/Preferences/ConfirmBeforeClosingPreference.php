<?php

namespace App\Values\User\Preferences;

class ConfirmBeforeClosingPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
