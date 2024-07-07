<?php

namespace App\Casts;

use App\Values\LicenseInstance;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Log;
use Throwable;

class LicenseInstanceCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?LicenseInstance
    {
        try {
            return $value ? LicenseInstance::fromJsonObject(json_decode($value)) : null;
        } catch (Throwable) {
            Log::error('Failed to cast-get license instance', [
                'model' => $model,
                'key' => $key,
                'value' => $value,
                'attributes' => $attributes,
            ]);

            return null;
        }
    }

    /** @param ?LicenseInstance $value */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        try {
            return $value?->toJson();
        } catch (Throwable) {
            Log::error('Failed to cast-set license instance', [
                'model' => $model,
                'key' => $key,
                'value' => $value,
                'attributes' => $attributes,
            ]);

            return null;
        }
    }
}
