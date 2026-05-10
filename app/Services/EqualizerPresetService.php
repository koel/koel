<?php

namespace App\Services;

use App\Models\User;
use App\Values\EqualizerPreset;
use Illuminate\Support\Str;

class EqualizerPresetService
{
    public function addPresetForUser(User $user, EqualizerPreset $preset): EqualizerPreset
    {
        $saved = $preset->withId((string) Str::ulid());

        $updatedPresets = [
            ...$this->serializeExisting($user),
            $saved->toArray(),
        ];

        usort($updatedPresets, static fn (array $a, array $b): int => strcasecmp(
            (string) $a['name'],
            (string) $b['name'],
        ));

        $user->preferences = $user->preferences->set('equalizer_presets', $updatedPresets);
        $user->save();

        return $saved;
    }

    public function removePresetForUser(User $user, string $id): void
    {
        $remainingPresets = $user
            ->preferences
            ->equalizerPresets
            ->reject(static fn (EqualizerPreset $preset): bool => $preset->id === $id)
            ->map(static fn (EqualizerPreset $preset): array => $preset->toArray())
            ->values()
            ->all();

        $user->preferences = $user->preferences->set('equalizer_presets', $remainingPresets);
        $user->save();
    }

    /** @return array<int, array<string, mixed>> */
    private function serializeExisting(User $user): array
    {
        return array_map(
            static fn (EqualizerPreset $preset): array => $preset->toArray(),
            $user->preferences->equalizerPresets->all(),
        );
    }
}
