<?php

namespace App\Http\Requests\API;

/**
 * @property string $email
 * @property string $password
 */
class UserLoginRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }
}
