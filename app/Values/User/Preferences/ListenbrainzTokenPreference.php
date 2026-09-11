<?php

namespace App\Values\User\Preferences;

class ListenbrainzTokenPreference extends Preference
{
    public function getProperty(): string
    {
        return 'listenBrainzToken';
    }

    public function isCustomizable(): bool
    {
        return false;
    }
}
