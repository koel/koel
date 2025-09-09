<?php

namespace App\Http\Requests\API;

use App\Rules\CustomizableUserPreference;

/**
 * @property-read string $key
 * @property-read string $value
 */
class UpdateUserPreferencesRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', new CustomizableUserPreference()],
            'value' => 'sometimes',
        ];
    }
}
