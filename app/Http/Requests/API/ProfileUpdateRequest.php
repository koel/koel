<?php

namespace App\Http\Requests\API;

/**
 * @property string password
 */
class ProfileUpdateRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.auth()->user()->id,
        ];
    }
}
