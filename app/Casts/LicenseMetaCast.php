<?php

namespace App\Casts;

use App\Values\LicenseMeta;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Log;
use Throwable;

class LicenseMetaCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?LicenseMeta
    {
        try {
            return $value ? LicenseMeta::fromJsonObject(json_decode($value)) : null;
        } catch (Throwable) {
            Log::error('Failed to cast-get license meta', [
                'model' => $model,
                'key' => $key,
                'value' => $value,
                'attributes' => $attributes,
            ]);

            return null;
        }
    }

    /** @param ?LicenseMeta $value */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        try {
            return $value?->toJson();
        } catch (Throwable) {
            Log::error('Failed to cast-set license meta', [
                'model' => $model,
                'key' => $key,
                'value' => $value,
                'attributes' => $attributes,
            ]);

            return null;
        }
    }
}
