<?php

namespace App\Values\User\Preferences;

class ShowNowPlayingNotificationPreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
