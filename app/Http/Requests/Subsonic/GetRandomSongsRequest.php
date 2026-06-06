<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

class GetRandomSongsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'size' => ['integer', 'min:1', 'max:500'],
        ];
    }
}
