<?php

namespace App\Casts\Podcast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PhanAn\Poddle\Values\Enclosure;

class EnclosureCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Enclosure
    {
        return Enclosure::fromArray(json_decode($value, true));
    }

    /** @param Enclosure|array $value */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (is_array($value)) {
            $value = Enclosure::fromArray($value);
        }

        return $value->toJson();
    }
}
