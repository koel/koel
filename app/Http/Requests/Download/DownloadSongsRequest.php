<?php

namespace App\Http\Requests\Download;

/**
 * @property array $songs
 */
class DownloadSongsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => 'required|array',
        ];
    }
}
