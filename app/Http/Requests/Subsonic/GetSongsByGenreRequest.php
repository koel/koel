<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $genre
 */
class GetSongsByGenreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'genre' => ['required', 'string'],
            'count' => ['integer', 'min:1', 'max:500'],
            'offset' => ['integer', 'min:0'],
        ];
    }
}
