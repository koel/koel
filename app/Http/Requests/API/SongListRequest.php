<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read ?string $cursor
 */
class SongListRequest extends Request
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
