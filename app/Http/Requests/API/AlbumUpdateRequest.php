<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $name
 * @property-read int|null $year
 */
class AlbumUpdateRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'name' => 'string|required',
            'year' => 'integer|nullable',
        ];
    }
}
