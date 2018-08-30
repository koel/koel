<?php

namespace App\Http\Requests\API;

/**
 * @property string q
 */
class ViewSongOnITunesRequest extends Request
{
    public function rules(): array
    {
        return [
            'q' => 'required',
        ];
    }
}
