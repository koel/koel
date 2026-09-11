<?php

namespace App\Http\Requests\API;

use App\Rules\ValidListenBrainzToken;

/**
 * @property string $token
 */
class SetListenBrainzTokenRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return ['token' => ['required', 'string', new ValidListenBrainzToken()]];
    }
}
