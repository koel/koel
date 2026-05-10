<?php

namespace App\Values\User\Preferences;

use App\Values\EqualizerPreset;
use App\Values\EqualizerPresetCollection;

class EqualizerPresetsPreference extends Preference
{
    public function getDefaultValue(): EqualizerPresetCollection
    {
        return new EqualizerPresetCollection();
    }

    protected function cast(mixed $value): EqualizerPresetCollection
    {
        if ($value instanceof EqualizerPresetCollection) {
            return $value;
        }

        return EqualizerPresetCollection::fromArray(
            is_array($value)
                ? array_map(static fn (mixed $item): mixed => $item instanceof EqualizerPreset
                    ? $item->toArray()
                    : $item, $value) : [],
        );
    }
}
