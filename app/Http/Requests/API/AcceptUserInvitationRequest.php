<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $token
 * @property-read string $name
 * @property-read string $password
 */
class AcceptUserInvitationRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'token' => 'required',
            'password' => ['required', Password::defaults()],
        ];
    }
}
