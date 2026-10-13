<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\Concerns\ValidatesOptionalImage;
use App\Http\Requests\API\Request;
use App\Values\Album\AlbumUpdateData;

/**
 * @property-read string $name
 * @property-read ?int $year
 * @property-read ?string $cover
 * @property-read ?string $description
 */
class AlbumUpdateRequest extends Request
{
    use ValidatesOptionalImage;

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => ['string', 'required'],
            'year' => ['integer', 'nullable'],
            'cover' => self::optionalImageRules(),
            'description' => ['string', 'sometimes', 'nullable', 'max:16000'],
        ];
    }

    public function toDto(): AlbumUpdateData
    {
        return AlbumUpdateData::make(
            name: $this->name,
            year: $this->year ?: null,
            cover: $this->optionalImageInput('cover'),
            description: $this->has('description') ? (string) $this->description : null,
        );
    }
}
