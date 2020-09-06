<?php

namespace App\Http\Requests\API;

/**
 * @property string $email
 * @property string $password
 */
class UserLoginRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }
}
