<?php

namespace App\Http\Requests\API;

use App\Values\User\UserCreateData;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $password
 * @property-read string $name
 * @property-read string $email
 */
class UserStoreRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'is_admin' => 'sometimes',
        ];
    }

    public function toDto(): UserCreateData
    {
        return UserCreateData::make(
            name: $this->name,
            email: $this->email,
            plainTextPassword: $this->password,
            isAdmin: $this->boolean('is_admin'),
        );
    }
}
