<?php

namespace App\Http\Requests\API;

use App\Models\User;

/**
 * @property string $password
 */
class UserUpdateRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ];
    }
}
