<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $email
 */
class ForgotPasswordRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
