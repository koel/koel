<?php

namespace App\Values\User\Preferences;

class MakeUploadsPublicPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
