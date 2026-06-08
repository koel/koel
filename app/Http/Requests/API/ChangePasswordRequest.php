<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $current_password
 * @property-read string $new_password
 */
class ChangePasswordRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', Password::defaults()],
        ];
    }
}
