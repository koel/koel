<?php

namespace App\Http\Requests\API\Artist;

use App\Http\Requests\API\Request;
use App\Rules\ImageData;

/**
 * @property-read string $name
 * @property-read ?string $image
 */
class ArtistUpdateRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => 'string|required',
            'image' => ['string', 'nullable', new ImageData()],
        ];
    }
}
