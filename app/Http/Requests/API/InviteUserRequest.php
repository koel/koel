<?php

namespace App\Http\Requests\API;

use App\Http\Requests\API\Concerns\ValidatesRole;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $emails
 */
class InviteUserRequest extends Request
{
    use ValidatesRole;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            'emails.*' => [Rule::required(), Rule::email(), Rule::unique('users', 'email')],
            'role' => $this->roleRule(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function messages(): array
    {
        return [
            'emails.*.unique' => 'The email :input is already registered.',
        ];
    }
}
