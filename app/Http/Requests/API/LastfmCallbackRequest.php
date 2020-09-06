<?php

namespace App\Http\Requests\API;

/**
 * @property string $token  Lastfm's access token
 * @property string $api_token Koel's current user's token
 */
class LastfmCallbackRequest extends Request
{
    public function rules(): array
    {
        return [
            'token' => 'required',
            'api_token' => 'required',
        ];
    }
}
