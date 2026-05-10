<?php

namespace App\Values\User\Preferences;

class LastfmSessionKeyPreference extends Preference
{
    public function getProperty(): string
    {
        return 'lastFmSessionKey';
    }

    public function isCustomizable(): bool
    {
        return false;
    }
}
