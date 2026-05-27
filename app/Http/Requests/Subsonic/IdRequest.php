<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

class IdRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
        ];
    }

    public function id(): string
    {
        return (string) $this->input('id');
    }
}
