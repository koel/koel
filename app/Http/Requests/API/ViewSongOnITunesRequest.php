<?php

namespace App\Http\Requests\API;

/**
 * @property string $q
 * @property string api_token
 */
class ViewSongOnITunesRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'q' => 'required',
            'api_token' => 'required',
        ];
    }
}
