<?php

namespace App\Http\Requests\API;

class ConfirmEmailChangeRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'current' => ['required', 'string'],
            'token' => ['required', 'string'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->query();
    }
}
