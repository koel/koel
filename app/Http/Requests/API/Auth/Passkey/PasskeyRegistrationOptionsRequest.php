<?php

namespace App\Http\Requests\API\Auth\Passkey;

use App\Http\Requests\API\Auth\Passkey\Concerns\CarriesPasskeyCredential;
use App\Http\Requests\API\Request;

/**
 * @property-read string|null $password
 * @property-read string|null $code
 */
class PasskeyRegistrationOptionsRequest extends Request
{
    use CarriesPasskeyCredential;

    /** @inheritdoc */
    public function rules(): array
    {
        if ($this->has('credential')) {
            return self::credentialRules();
        }

        return [
            'password' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
        ];
    }
}
