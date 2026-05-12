<?php

namespace App\Values\User\Preferences;

class ArtistsFavoritesOnlyPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
