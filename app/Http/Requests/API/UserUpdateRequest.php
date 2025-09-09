<?php

namespace App\Http\Requests\API;

use App\Models\User;
use App\Values\User\UserUpdateData;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $password
 * @property-read string $name
 * @property-read string $email
 */
class UserUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['sometimes', Password::defaults()],
            'is_admin' => 'sometimes',
        ];
    }

    public function toDto(): UserUpdateData
    {
        return UserUpdateData::make(
            name: $this->name,
            email: $this->email,
            plainTextPassword: $this->password,
            isAdmin: $this->has('is_admin') ? $this->boolean('is_admin') : null,
        );
    }
}
