<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

class ConfirmEmailChangeRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => [Rule::required(), Rule::email()],
            'current' => [Rule::required(), Rule::string()],
            'token' => [Rule::required(), Rule::string()],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->query();
    }
}
