<?php

namespace App\Http\Requests;

/**
 * @property-read string $email
 * @property-read string $current
 */
class ConfirmEmailChangeRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'current' => ['required', 'string'],
        ];
    }
}
