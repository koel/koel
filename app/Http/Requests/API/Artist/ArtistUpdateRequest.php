<?php

namespace App\Http\Requests\API\Artist;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Values\Artist\ArtistUpdateData;

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
            'image' => ['string', 'nullable', new ValidImageData()],
        ];
    }

    public function toDto(): ArtistUpdateData
    {
        return ArtistUpdateData::make(
            name: $this->name,
            image: $this->image ?: null,
        );
    }
}
