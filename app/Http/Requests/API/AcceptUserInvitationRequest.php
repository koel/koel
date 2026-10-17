<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $token
 * @property-read string $name
 * @property-read string $password
 */
class AcceptUserInvitationRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => [Rule::required()],
            'token' => [Rule::required()],
            'password' => [Rule::required(), Password::defaults()],
        ];
    }
}
