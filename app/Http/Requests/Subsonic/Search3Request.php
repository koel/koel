<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

class Search3Request extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string'],
            'artistCount' => ['integer', 'min:0', 'max:500'],
            'artistOffset' => ['integer', 'min:0'],
            'albumCount' => ['integer', 'min:0', 'max:500'],
            'albumOffset' => ['integer', 'min:0'],
            'songCount' => ['integer', 'min:0', 'max:500'],
            'songOffset' => ['integer', 'min:0'],
        ];
    }
}
