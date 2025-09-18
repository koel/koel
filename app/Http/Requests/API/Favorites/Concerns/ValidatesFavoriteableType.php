<?php

namespace App\Http\Requests\API\Favorites\Concerns;

use App\Enums\FavoriteableType;
use Illuminate\Validation\Rule;

trait ValidatesFavoriteableType
{
    /** @return array<string|Rule> */
    private static function favoriteableTypeRule(): array
    {
        return [
            'string',
            'required',
            Rule::enum(FavoriteableType::class),
        ];
    }
}
