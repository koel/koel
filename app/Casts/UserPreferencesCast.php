<?php

namespace App\Casts;

use App\Values\UserPreferences;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Arr;

class UserPreferencesCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): UserPreferences
    {
        $arr = json_decode($value, true) ?: [];

        return UserPreferences::make(Arr::get($arr, 'lastfm_session_key'));
    }

    /** @param UserPreferences|null $value */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        return json_encode($value ?: []);
    }
}
