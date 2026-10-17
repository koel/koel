<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;
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
            'current_password' => [Rule::required(), Rule::string()],
            'new_password' => [Rule::required(), Password::defaults()],
        ];
    }
}
