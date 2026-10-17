<?php

namespace App\Http\Requests\API;

use App\Values\User\AvatarUpdateData;
use App\Values\User\UserUpdateData;
use Illuminate\Validation\Rule;

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
            'name' => [Rule::required()],
            'email' => [
                Rule::required(),
                Rule::email(),
                Rule::unique('users', 'email')->ignore(auth()->user()->getAuthIdentifier()),
            ],
            'avatar' => [Rule::sometimes(), Rule::nullable(), Rule::string(), 'starts_with:data:image/'],
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
