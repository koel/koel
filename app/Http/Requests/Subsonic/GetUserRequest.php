<?php

namespace App\Http\Requests\Subsonic;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * @property string $username
 */
class GetUserRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'username' => [Rule::required(), Rule::string()],
        ];
    }
}
