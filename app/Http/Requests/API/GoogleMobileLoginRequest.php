<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $id_token
 */
class GoogleMobileLoginRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'id_token' => 'required|string',
        ];
    }
}
