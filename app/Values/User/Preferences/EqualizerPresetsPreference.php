<?php

namespace App\Values\User\Preferences;

use App\Values\EqualizerPreset;
use App\Values\EqualizerPresetCollection;

class EqualizerPresetsPreference extends Preference
{
    /**
     * Presets are saved and deleted through the `me/equalizer-presets` endpoints, which validate them.
     * Saving them through the generic preferences endpoint would skip that validation. Marking this preference
     * as not customizable makes that endpoint refuse it.
     */
    public function isCustomizable(): bool
    {
        return false;
    }

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
