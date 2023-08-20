<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $email
 */
class RevokeUserInvitationRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return ['email' => 'required|email'];
    }
}
