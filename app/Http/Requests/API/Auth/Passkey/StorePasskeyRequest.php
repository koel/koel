<?php

namespace App\Http\Requests\API\Auth\Passkey;

use App\Http\Requests\API\Auth\Passkey\Concerns\CarriesPasskeyCredential;
use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 */
class StorePasskeyRequest extends Request
{
    use CarriesPasskeyCredential;

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::required(), Rule::string()->max(255)],
            ...self::credentialRules(),
        ];
    }
}
