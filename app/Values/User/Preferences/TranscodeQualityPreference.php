<?php

namespace App\Values\User\Preferences;

class TranscodeQualityPreference extends Preference
{
    private const array ALLOWED = [64, 96, 128, 192, 256, 320];

    public function getDefaultValue(): int
    {
        return (int) config('koel.streaming.bitrate');
    }

    protected function cast(mixed $value): int
    {
        $cast = (int) $value;

        return in_array($cast, self::ALLOWED, true) ? $cast : 128;
    }
}
