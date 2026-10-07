<?php

namespace App\Http\Requests\API;

use App\Enums\Acl\Role;
use App\Http\Requests\API\Concerns\ValidatesRole;
use App\Values\User\UserCreateData;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $password
 * @property-read string $name
 * @property-read string $email
 */
class UserStoreRequest extends Request
{
    use ValidatesRole;

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role' => $this->roleRule(),
        ];
    }

    public function toDto(): UserCreateData
    {
        return UserCreateData::make(
            name: $this->name,
            email: $this->email,
            plainTextPassword: $this->password,
            role: $this->enum('role', Role::class),
        );
    }
}
