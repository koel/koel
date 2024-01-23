<?php

namespace App\Casts;

use App\Values\UserPreferences;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class UserPreferencesCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): UserPreferences
    {
        return UserPreferences::fromArray(json_decode($value, true) ?: []);
    }

    /** @param UserPreferences|null $value */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        return json_encode($value ?: []);
    }
}
