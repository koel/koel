<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $token
 * @property-read string $email
 * @property-read string $password
 */
class ResetPasswordRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['sometimes', Password::defaults()],
        ];
    }
}
