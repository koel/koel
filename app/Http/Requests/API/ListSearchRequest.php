<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $q
 */
class ListSearchRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'q' => ['required'],
            'favorites_only' => ['sometimes', 'boolean'],
        ];
    }
}
