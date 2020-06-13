<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $password
 * @property-read string $name
 * @property-read string $email
 * @property-read bool $is_admin
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
            'is_admin' => 'required'
        ];
    }
}
