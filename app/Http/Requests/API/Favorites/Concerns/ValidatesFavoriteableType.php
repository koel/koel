<?php

namespace App\Http\Requests\API\Favorites\Concerns;

use App\Enums\FavoriteableType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait ValidatesFavoriteableType
{
    /** @return array<string|Enum> */
    private static function favoriteableTypeRule(): array
    {
        return [
            'string',
            'required',
            Rule::enum(FavoriteableType::class),
        ];
    }
}
