<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $playlistId
 */
class UpdatePlaylistRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'playlistId' => ['required', 'string'],
            'name' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
