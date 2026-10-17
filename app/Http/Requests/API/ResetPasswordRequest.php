<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $token
 * @property-read string $email
 * @property-read string $password
 */
class ResetPasswordRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'token' => [Rule::required()],
            'email' => [Rule::required(), Rule::email()],
            'password' => [Rule::sometimes(), Password::defaults()],
        ];
    }
}
