<?php

namespace App\Http\Requests\API;

/**
 * @property string key
 */
class LastfmSetSessionKeyRequest extends Request
{
    public function rules(): array
    {
        return [
            'key' => 'required',
        ];
    }
}
