<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

class CreatePlaylistRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => ['required_without:playlistId', 'string'],
            'playlistId' => ['nullable', 'string'],
        ];
    }
}
