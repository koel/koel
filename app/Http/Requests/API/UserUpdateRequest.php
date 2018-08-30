<?php

namespace App\Http\Requests\API;

/**
 * @property string password
 */
class UserUpdateRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$this->route('user')->id,
        ];
    }
}
