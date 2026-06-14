<?php

namespace App\Http\Requests\API\Album;

use App\Http\Requests\API\Request;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read ?string $cursor
 * @property-read ?int $per_page
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('per_page')) {
            $this->merge(['per_page' => (int) $this->input('per_page')]);
        }
    }
}
