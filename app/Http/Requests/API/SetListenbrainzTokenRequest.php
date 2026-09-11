<?php

namespace App\Http\Requests\API;

use App\Rules\ValidListenbrainzToken;

/**
 * @property string $token
 */
class SetListenbrainzTokenRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return ['token' => ['required', 'string', new ValidListenbrainzToken()]];
    }
}
