<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $email
 * @property-read string $current
 * @property-read string $token
 */
class ConfirmEmailChangeRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'current' => ['required', 'string'],
            'token' => ['required', 'string'],
        ];
    }
}
