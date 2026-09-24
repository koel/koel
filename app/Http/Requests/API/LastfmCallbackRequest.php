<?php

namespace App\Http\Requests\API;

/**
 * @property string $token Lastfm's access token
 * @property string $state
 */
class LastfmCallbackRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'token' => 'required',
            'state' => 'required',
        ];
    }
}
