<?php

namespace App\Values\User\Preferences;

class ShowAlbumArtOverlayPreference extends Preference
{
    public function getDefaultValue(): true
    {
        return true;
    }

    protected function cast(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
