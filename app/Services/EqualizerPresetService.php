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

        $next = [
            ...$this->serializeExisting($user),
            $saved->toArray(),
        ];

        $user->preferences = $user->preferences->set('equalizer_presets', $next);
        $user->save();

        return $saved;
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
    private function serializeExisting(User $user): array
    {
        return $user->preferences->equalizerPresets->map(
            static fn (EqualizerPreset $preset): array => $preset->toArray(),
        )->all();
    }
}
