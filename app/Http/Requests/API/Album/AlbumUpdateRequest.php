<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;

/**
 * @property-read string $name
 * @property-read int|null $year
 * @property-read string|null $cover
 */
class AlbumUpdateRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => 'string|required',
            'year' => 'integer|nullable',
            'cover' => ['string', 'nullable', new ValidImageData()],
        ];
    }
}
