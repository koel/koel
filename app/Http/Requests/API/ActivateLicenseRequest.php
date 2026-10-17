<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property-read string $key
 */
class ActivateLicenseRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'key' => [Rule::required(), Rule::string()],
        ];
    }
}
