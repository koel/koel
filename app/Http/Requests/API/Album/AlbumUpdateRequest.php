<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\Request;
use App\Rules\ValidImageData;
use App\Values\Album\AlbumUpdateData;

/**
 * @property-read string $name
 * @property-read ?int $year
 * @property-read ?string $cover
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

    public function toDto(): AlbumUpdateData
    {
        return AlbumUpdateData::make(
            name: $this->name,
            year: $this->year ?: null,
            cover: $this->cover ?: null,
        );
    }
}
