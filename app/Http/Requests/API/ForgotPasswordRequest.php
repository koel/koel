<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $email
 */
class ForgotPasswordRequest extends Request
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }
}
