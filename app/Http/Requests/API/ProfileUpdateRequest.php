<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rules\Password;

/**
 * @property-read string|null $current_password
 * @property-read string|null $new_password
 * @property-read string $name
 * @property-read string $email
 * @property-read string|null $avatar
 */
class ProfileUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . auth()->user()->id,
            'current_password' => 'required',
            'new_password' => ['sometimes', Password::defaults()],
        ];
    }
}
