<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read ?string $current_password
 * @property-read string $new_password
 */
class ChangePasswordRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'current_password' => [
                Rule::requiredIf(fn (): bool => !$this->user()->can_set_password_without_current_one),
                Rule::string(),
            ],
            'new_password' => [Rule::required(), Password::defaults()],
        ];
    }
}
