<?php

namespace App\Http\Requests\API;

use App\Values\User\AvatarUpdateData;
use App\Values\User\UserUpdateData;

/**
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
            'avatar' => ['sometimes', 'nullable', 'string', 'starts_with:data:image/'],
        ];
    }

    public function toDto(): UserUpdateData
    {
        return UserUpdateData::make(
            name: $this->name,
            email: $this->email,
            avatar: $this->has('avatar') ? AvatarUpdateData::make($this->avatar) : null,
        );
    }
}
