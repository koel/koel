<?php

namespace App\Http\Requests\API;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

/**
 * @property string $password
 * @property string $name
 * @property string $email
 * @property bool   $is_admin
 */
class UserUpdateRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    /** @return array<mixed> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['sometimes', Password::defaults()],
        ];
    }
}
