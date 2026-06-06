<?php

namespace App\Values\User\Preferences;

class AlbumsFavoritesOnlyPreference extends BooleanPreference
{
    public function getDefaultValue(): false
    {
        return false;
    }
}
