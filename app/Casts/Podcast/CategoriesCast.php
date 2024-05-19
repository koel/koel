<?php

namespace App\Casts\Podcast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PhanAn\Poddle\Values\CategoryCollection;
use Throwable;

class CategoriesCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): CategoryCollection
    {
        try {
            return CategoryCollection::fromArray($value ? json_decode($value, true) : []);
        } catch (Throwable) {
            return CategoryCollection::make();
        }
    }

    /** @param CategoryCollection|array<mixed> $value */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (is_array($value)) {
            $value = CategoryCollection::fromArray($value);
        }

        return $value->toJson();
    }
}
