<?php

namespace App\Http\Requests\API\Artist;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Values\Artist\ArtistUpdateData;

/**
 * @property-read string $name
 * @property-read ?string $image
 * @property-read ?string $description
 */
class ArtistUpdateRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => ['string', 'required'],
            'image' => ['string', 'sometimes', 'nullable', new ValidImageData()],
            'description' => ['string', 'sometimes', 'nullable'],
        ];
    }

    public function toDto(): ArtistUpdateData
    {
        return ArtistUpdateData::make(
            name: $this->name,
            // null means no change to the image, when an empty string means to delete the image
            image: $this->has('image') ? $this->string('image') : null,
            description: $this->has('description') ? (string) $this->description : null,
        );
    }
}
