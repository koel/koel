<?php

namespace App\Http\Requests\API\Favorites;

use App\Http\Requests\API\Favorites\Concerns\ValidatesFavoriteableType;
use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read string $type
 * @property-read string $id
 */
class ToggleFavoriteRequest extends Request
{
    use ValidatesFavoriteableType;

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'type' => self::favoriteableTypeRule(),
            'id' => [Rule::required(), Rule::string()],
        ];
    }
}
