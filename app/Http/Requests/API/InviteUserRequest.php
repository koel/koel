<?php

namespace App\Http\Requests\API;

/**
 * @property-read array<string> $emails
 */
class InviteUserRequest extends Request
{
    /**
     * @return array<mixed>
     */
    public function rules(): array
    {
        return [
            'emails.*' => 'required|email|unique:users,email',
            'is_admin' => 'sometimes',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function messages(): array
    {
        return [
            'emails.*.unique' => 'The email :input is already registered.',
        ];
    }
}
