<?php

namespace App\Http\Requests\API\Auth\Passkey;

use App\Http\Requests\API\Auth\Passkey\Concerns\CarriesPasskeyCredential;
use App\Http\Requests\API\Request;

/**
 * @property-read string $login_token
 */
class PasskeyLoginRequest extends Request
{
    use CarriesPasskeyCredential;

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'login_token' => ['required', 'string'],
            ...self::credentialRules(),
        ];
    }
}
