<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $key
 */
class ActivateLicenseRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'key' => 'required|string',
        ];
    }
}
