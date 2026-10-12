<?php

namespace App\Casts;

use App\Values\User\UserPreferences;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

class UserPreferencesCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): UserPreferences
    {
        return UserPreferences::fromArray(self::decryptSecrets(json_decode($value, true) ?: []));
    }

    /** @param UserPreferences|array|null $value */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        $preferences = $value instanceof UserPreferences ? $value->toArray() : (array) $value;

        return json_encode(self::encryptSecrets($preferences));
    }

    private static function encryptSecrets(array $preferences): array
    {
        foreach (UserPreferences::encryptedKeys() as $secretKey) {
            if (!is_string(Arr::get($preferences, $secretKey))) {
                continue;
            }

            $preferences[$secretKey] = Crypt::encryptString($preferences[$secretKey]);
        }

        return $preferences;
    }

    private static function decryptSecrets(array $preferences): array
    {
        foreach (UserPreferences::encryptedKeys() as $secretKey) {
            if (!is_string(Arr::get($preferences, $secretKey))) {
                continue;
            }

            try {
                $preferences[$secretKey] = Crypt::decryptString($preferences[$secretKey]);
            } catch (DecryptException) {
                $preferences[$secretKey] = null;
            }
        }

        return $preferences;
    }
}
