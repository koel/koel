<?php

namespace App\Http\Requests\API\Download;

/**
 * @property array songs
 */
class SongRequest extends Request
{
    public function rules(): array
    {
        return [
            'songs' => 'required|array',
        ];
    }
}
