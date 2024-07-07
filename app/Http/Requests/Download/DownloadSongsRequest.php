<?php

namespace App\Http\Requests\Download;

/**
 * @property array $songs
 */
class DownloadSongsRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|array',
        ];
    }
}
