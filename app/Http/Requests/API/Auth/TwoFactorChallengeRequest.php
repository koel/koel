<?php

namespace App\Http\Requests\API\Auth;

use App\Http\Requests\API\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read string $login_token
 * @property-read string $code
 */
class TwoFactorChallengeRequest extends Request
{
    /** @inheritdoc  */
    public function rules(): array
    {
        return [
            'login_token' => [Rule::required(), Rule::string()],
            'code' => [Rule::required(), Rule::string()],
        ];
    }
}
