<?php

namespace App\Http\Requests;

/**
 * @property array $songs
 */
class DownloadSongRequest extends Request
{
    public function authorize(): bool
    {
        return config('koel.download.allow');
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => ['required', 'array', 'size:1'],
        ];
    }
}
