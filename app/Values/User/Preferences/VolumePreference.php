<?php

namespace App\Values\User\Preferences;

class VolumePreference extends Preference
{
    public function getDefaultValue(): float
    {
        return 7.0;
    }

    protected function cast(mixed $value): float
    {
        return (float) $value;
    }
}
