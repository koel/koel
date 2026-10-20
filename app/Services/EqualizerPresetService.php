<?php

namespace App\Services;

use App\Models\User;
use App\Values\EqualizerPreset;
use App\Values\User\UserPreferences;
use Illuminate\Support\Str;

class EqualizerPresetService
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function addPresetForUser(User $user, EqualizerPreset $preset): EqualizerPreset
    {
        $saved = $preset->withId((string) Str::ulid());

        $this->userService->updatePreferences($user, static function (UserPreferences $preferences) use (
            $saved,
        ): UserPreferences {
            $updatedPresets = [
                ...self::serializePresets($preferences),
                $saved->toArray(),
            ];

            usort($updatedPresets, static fn (array $a, array $b): int => strcasecmp(
                (string) $a['name'],
                (string) $b['name'],
            ));

            return $preferences->set('equalizer_presets', $updatedPresets);
        });

        return $saved;
    }

    public function removePresetForUser(User $user, string $id): void
    {
        $this->userService->updatePreferences($user, static function (UserPreferences $preferences) use (
            $id,
        ): UserPreferences {
            $remainingPresets = $preferences
                ->equalizerPresets
                ->reject(static fn (EqualizerPreset $preset): bool => $preset->id === $id)
                ->map(static fn (EqualizerPreset $preset): array => $preset->toArray())
                ->values()
                ->all();

            return $preferences->set('equalizer_presets', $remainingPresets);
        });
    }

    /** @return array<int, array<string, mixed>> */
    private static function serializePresets(UserPreferences $preferences): array
    {
        return array_map(
            static fn (EqualizerPreset $preset): array => $preset->toArray(),
            $preferences->equalizerPresets->all(),
        );
    }
}
