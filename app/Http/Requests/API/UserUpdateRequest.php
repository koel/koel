<?php

namespace App\Http\Requests\API;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string $password
 * @property-read string $name
 * @property-read string $email
 */
class UserUpdateRequest extends Request
{
    /** @return array<mixed> */
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
}
