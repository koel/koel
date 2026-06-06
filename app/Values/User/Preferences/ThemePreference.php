<?php

namespace App\Values\User\Preferences;

class ThemePreference extends Preference
{
    public function getDefaultValue(): string
    {
        return 'classic';
    }
}
