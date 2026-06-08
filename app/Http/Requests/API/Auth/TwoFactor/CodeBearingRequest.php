<?php

namespace App\Http\Requests\API\Auth\TwoFactor;

use App\Http\Requests\API\Request;

/** @property-read string $code */
class CodeBearingRequest extends Request
{
    /** @inheritdoc  */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }
}
