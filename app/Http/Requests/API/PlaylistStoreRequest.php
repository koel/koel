<?php

namespace App\Http\Requests\API;

/**
 * @property array<string> $songs
 * @property string   $name
 * @property array    $rules
 */
class PlaylistStoreRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'songs' => 'array',
        ];
    }
}
