<?php

namespace App\Http\Requests\API;

use App\Values\User\UserUpdateData;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string|null $current_password
 * @property-read string|null $new_password
 * @property-read string $name
 * @property-read string $email
 * @property-read string|null $avatar
 */
class ProfileUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . auth()->user()->getAuthIdentifier(),
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => ['sometimes', Password::defaults()],
            'avatar' => 'sometimes',
        ];
    }

    public function toDto(): UserUpdateData
    {
        return UserUpdateData::make(
            name: $this->name,
            email: $this->email,
            plainTextPassword: $this->new_password,
            avatar: Str::startsWith($this->avatar, 'data:') ? $this->avatar : null,
        );
    }
}
