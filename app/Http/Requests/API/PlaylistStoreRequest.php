<?php

namespace App\Http\Requests\API;

/**
 * @property string[] $songs
 * @property string   $name
 * @property array    $rules
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
