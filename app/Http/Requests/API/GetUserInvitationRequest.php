<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $token
 */
class GetUserInvitationRequest extends Request
{
    /**
     * @return array<mixed>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
        ];
    }
}
