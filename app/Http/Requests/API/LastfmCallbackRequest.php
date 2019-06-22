<?php

namespace App\Http\Requests\API;

/**
 * @property string $token
 */
class LastfmCallbackRequest extends Request
{
    public function rules(): array
    {
        return [
            'token' => 'required',
        ];
    }
}
