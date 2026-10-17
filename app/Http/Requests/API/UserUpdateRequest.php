<?php

namespace App\Http\Requests\API;

use App\Enums\Acl\Role;
use App\Http\Requests\API\Concerns\ValidatesRole;
use App\Models\User;
use App\Values\User\UserUpdateData;
use Illuminate\Validation\Rule;
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
            'name' => [Rule::required()],
            'email' => [Rule::required(), Rule::email(), Rule::unique('users', 'email')->ignore($target->id)],
            'password' => [Rule::sometimes(), Password::defaults()],
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
