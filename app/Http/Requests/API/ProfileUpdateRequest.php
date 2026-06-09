<?php

namespace App\Http\Requests\API;

use App\Values\User\UserUpdateData;
use Illuminate\Support\Str;

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
            'avatar' => 'sometimes',
        ];
    }

    public function toDto(): UserUpdateData
    {
        return UserUpdateData::make(
            name: $this->name,
            email: $this->email,
            avatar: Str::startsWith($this->avatar, 'data:') ? $this->avatar : null,
        );
    }
}
