<?php

namespace App\Values\User\Preferences;

class ShowAlbumArtOverlayPreference extends BooleanPreference
{
    public function getDefaultValue(): true
    {
        return true;
    }
}
