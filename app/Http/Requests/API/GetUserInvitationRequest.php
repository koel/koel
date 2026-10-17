<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $token
 */
class GetUserInvitationRequest extends Request
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            'token' => [Rule::required(), Rule::string()],
        ];
    }
}
