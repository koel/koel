<?php

namespace App\Http\Requests\API;

/**
 * @property string[] $songs
 */
class PlaylistStoreRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required',
            'songs' => 'array',
        ];
    }
}
