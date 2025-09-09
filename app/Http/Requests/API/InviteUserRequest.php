<?php

namespace App\Http\Requests\API;

/**
 * @property-read array<string> $emails
 */
class InviteUserRequest extends Request
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            'emails.*' => 'required|email|unique:users,email',
            'is_admin' => 'sometimes',
        ];
    }

    /**
     * @inheritdoc
     */
    public function messages(): array
    {
        return [
            'emails.*.unique' => 'The email :input is already registered.',
        ];
    }
}
