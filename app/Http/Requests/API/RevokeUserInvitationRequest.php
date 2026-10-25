<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $email
 */
class RevokeUserInvitationRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => [
                Rule::required(),
                Rule::email(),
                Rule::unique('users', 'email')->whereNot('organization_id', $this->user()->organization_id),
            ],
        ];
    }
}
