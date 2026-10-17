<?php

namespace App\Http\Requests\API;

use App\Rules\ValidListenBrainzToken;
use Illuminate\Validation\Rule;

/**
 * @property string $token
 */
class SetListenBrainzTokenRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return ['token' => [Rule::bail(), Rule::required(), Rule::string(), new ValidListenBrainzToken()]];
    }
}
