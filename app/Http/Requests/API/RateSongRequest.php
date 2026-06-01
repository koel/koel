<?php

namespace App\Http\Requests\API;

use App\Http\Requests\Request;

/**
 * @property int $rating
 */
class RateSongRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }
}
