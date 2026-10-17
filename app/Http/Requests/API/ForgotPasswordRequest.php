<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $email
 */
class ForgotPasswordRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => [Rule::required(), Rule::email()],
        ];
    }
}
