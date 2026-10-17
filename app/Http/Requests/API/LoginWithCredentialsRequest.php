<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property string $email
 * @property string $password
 */
class LoginWithCredentialsRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => [Rule::required(), Rule::email()],
            'password' => [Rule::required()],
        ];
    }
}
