<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property ?string $artist
 * @property ?string $title
 */
class GetLyricsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'artist' => ['nullable', 'string'],
            'title' => ['nullable', 'string'],
        ];
    }
}
