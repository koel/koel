<?php

namespace App\Casts;

use App\Enums\SongStorageType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SongStorageCast implements CastsAttributes
{
    /** @param string|null $value */
    public function get(Model $model, string $key, mixed $value, array $attributes): SongStorageType
    {
        return SongStorageType::tryFrom($value) ?? SongStorageType::LOCAL;
    }

    /** @param SongStorageType|string|null $value */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $type = $value instanceof SongStorageType ? $value : SongStorageType::tryFrom($value);

        return $type->value;
    }
}
