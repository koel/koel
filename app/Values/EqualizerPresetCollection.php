<?php

namespace App\Values;

use Illuminate\Support\Collection;

/** @extends Collection<int, EqualizerPreset> */
final class EqualizerPresetCollection extends Collection
{
    public static function fromArray(mixed $data): self
    {
        if (!is_array($data)) {
            return new self();
        }

        $presets = array_map(EqualizerPreset::tryFromArray(...), $data);

        return new self(array_values(array_filter(
            $presets,
            static fn (?EqualizerPreset $preset) => (
                $preset !== null
                && $preset->id !== null
                && $preset->name !== null
                && $preset->name !== ''
            ),
        )));
    }
}
