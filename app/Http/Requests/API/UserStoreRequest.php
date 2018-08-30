<?php

namespace App\Http\Requests\API;

/**
 * @property string password
 * @property string name
 * @property string email
 */
class UserStoreRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
        ];
    }
}
