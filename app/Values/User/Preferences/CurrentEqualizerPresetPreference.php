<?php

namespace App\Values\User\Preferences;

use App\Values\EqualizerPreset;

class CurrentEqualizerPresetPreference extends Preference
{
    public function getDefaultValue(): EqualizerPreset
    {
        return EqualizerPreset::default();
    }

    public function getAliases(): array
    {
        return ['equalizer'];
    }

    protected function cast(mixed $value): EqualizerPreset
    {
        if ($value instanceof EqualizerPreset) {
            return $value;
        }

        return EqualizerPreset::tryFromArray($value) ?? EqualizerPreset::default();
    }
}
