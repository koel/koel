<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Values\Album\AlbumUpdateData;

/**
 * @property-read string $name
 * @property-read ?int $year
 * @property-read ?string $cover
 * @property-read ?string $description
 */
class AlbumUpdateRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => ['string', 'required'],
            'year' => ['integer', 'nullable'],
            'cover' => ['string', 'sometimes', 'nullable', new ValidImageData()],
            'description' => ['string', 'sometimes', 'nullable'],
        ];
    }

    public function toDto(): AlbumUpdateData
    {
        return AlbumUpdateData::make(
            name: $this->name,
            year: $this->year ?: null,
            cover: $this->has('cover') ? $this->string('cover') : null,
            description: $this->has('description') ? (string) $this->description : null,
        );
    }
}
