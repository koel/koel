<?php

namespace App\Values\User\Preferences;

class LyricsZoomLevelPreference extends Preference
{
    public function getDefaultValue(): int
    {
        return 1;
    }

    protected function cast(mixed $value): int
    {
        return (int) $value;
    }
}
