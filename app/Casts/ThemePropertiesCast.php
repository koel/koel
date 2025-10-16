<?php

namespace App\Casts;

use App\Values\Theme\ThemeProperties;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Log;
use Throwable;

class ThemePropertiesCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ThemeProperties
    {
        try {
            return $value ? ThemeProperties::unserialize(json_decode($value)) : ThemeProperties::empty();
        } catch (Throwable $e) {
            Log::error('Failed to cast-get theme properties', [
                'model' => $model,
                'key' => $key,
                'value' => $value,
                'attributes' => $attributes,
            ]);

            return  ThemeProperties::empty();
        }
    }

    /** @param ?ThemeProperties $value */
    public function set($model, string $key, $value, array $attributes): string
    {
        try {
            return $value?->serialize();
        } catch (Throwable) {
            Log::error('Failed to cast-set theme properties', [
                'model' => $model,
                'key' => $key,
                'value' => $value,
                'attributes' => $attributes,
            ]);

            return '';
        }
    }
}
