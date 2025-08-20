<?php

namespace App\Http\Requests\API\Favorites\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesFavoriteableType
{
    /** @return array<string|Rule> */
    private static function favoriteableTypeRule(): array
    {
        return [
            'string',
            'required',
            Rule::in(['playable', 'album', 'artist', 'podcast', 'radio-station']),
        ];
    }
}
