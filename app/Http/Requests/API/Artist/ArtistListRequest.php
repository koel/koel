<?php

namespace App\Http\Requests\API\Artist;

use App\Http\Requests\API\Request;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read ?string $cursor
 */
class ArtistListRequest extends Request
{
    /** @inheritDoc */
    public function rules(): array
    {
        return [
            'sort' => ['sometimes', 'string'],
            'order' => ['sometimes', 'in:asc,desc'],
            'cursor' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
