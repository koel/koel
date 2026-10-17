<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

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
            'token' => [Rule::required()],
            'state' => [Rule::required()],
        ];
    }
}
