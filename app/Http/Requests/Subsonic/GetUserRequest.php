<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;

/**
 * @property string $username
 */
class GetUserRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
        ];
    }
}
