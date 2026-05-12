<?php

namespace App\Values\User\Preferences;

class PodcastsFavoritesOnlyPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
