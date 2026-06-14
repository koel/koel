<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\Request;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read ?string $cursor
 */
class AlbumListRequest extends Request
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
