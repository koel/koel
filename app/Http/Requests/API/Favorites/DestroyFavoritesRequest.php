<?php

namespace App\Http\Requests\API\Favorites;

use App\Http\Requests\API\Favorites\Concerns\ValidatesFavoriteableType;
use App\Http\Requests\API\Request;

/**
 * @property-read string $type
 * @property-read array<string> $ids
 */
class DestroyFavoritesRequest extends Request
{
    use ValidatesFavoriteableType;

    /** @inheritdoc  */
    public function rules(): array
    {
        return [
            'type' => self::favoriteableTypeRule(),
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string'],
        ];
    }
}
