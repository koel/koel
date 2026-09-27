<?php

namespace App\Http\Requests\Download;

/**
 * @property array $songs
 */
class DownloadSongRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => ['required', 'array', 'size:1'],
        ];
    }
}
