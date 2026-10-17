<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

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
            'songs' => [Rule::required(), Rule::array(), 'size:1'],
        ];
    }
}
