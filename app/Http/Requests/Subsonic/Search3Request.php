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
            'albumCount' => ['integer', 'min:0', 'max:500'],
            'songCount' => ['integer', 'min:0', 'max:500'],
        ];
    }
}
