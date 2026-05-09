<?php

namespace App\Services;

use App\Models\User;
use App\Values\EqualizerPreset;
use Illuminate\Support\Str;

class EqualizerPresetService
{
    /** @param array<int, float|int> $gains */
    public function addPresetForUser(User $user, string $name, float $preamp, array $gains): EqualizerPreset
    {
        $preset = EqualizerPreset::make(id: (string) Str::ulid(), name: $name, preamp: $preamp, gains: $gains);

        $next = [
            ...$this->serialiseExisting($user),
            $preset->toArray(),
        ];

        $user->preferences = $user->preferences->set('equalizer_presets', $next);
        $user->save();

        return $preset;
    }

    public function removePresetForUser(User $user, string $id): void
    {
        $next = $user
            ->preferences
            ->equalizerPresets
            ->reject(static fn (EqualizerPreset $preset): bool => $preset->id === $id)
            ->map(static fn (EqualizerPreset $preset): array => $preset->toArray())
            ->values()
            ->all();

        $user->preferences = $user->preferences->set('equalizer_presets', $next);
        $user->save();
    }

    /** @return array<int, array<string, mixed>> */
    private function serialiseExisting(User $user): array
    {
        return $user->preferences->equalizerPresets->map(
            static fn (EqualizerPreset $preset): array => $preset->toArray(),
        )->all();
    }
}
