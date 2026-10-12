<?php

namespace App\Http\Requests\API;

use App\Enums\Acl\Role;
use App\Http\Requests\API\Concerns\ValidatesRole;
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
    use ValidatesRole;

    /** @inheritdoc */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $target->id,
            'password' => ['sometimes', Password::defaults()],
            'role' => $this->roleRule(),
        ];
    }

    public function toDto(): UserUpdateData
    {
        return UserUpdateData::make(
            name: $this->name,
            email: $this->email,
            plainTextPassword: $this->password,
            role: $this->enum('role', Role::class),
        );
    }
}
