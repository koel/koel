<?php

namespace App\Http\Requests\API;

use App\Enums\Acl\Role;
use App\Rules\AvailableRole;
use App\Rules\UserCanManageRole;
use Illuminate\Validation\Rule;

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
            'role' => [
                'required',
                Rule::enum(Role::class),
                new AvailableRole(),
                new UserCanManageRole($this->user()),
            ],
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
