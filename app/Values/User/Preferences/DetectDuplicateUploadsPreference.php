<?php

namespace App\Values\User\Preferences;

class DetectDuplicateUploadsPreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
