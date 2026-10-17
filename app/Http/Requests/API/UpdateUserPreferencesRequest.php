<?php

namespace App\Http\Requests\API;

use App\Rules\CustomizableUserPreference;
use Illuminate\Validation\Rule;

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
            'key' => [Rule::required(), Rule::string(), new CustomizableUserPreference()],
            'value' => [Rule::sometimes()],
        ];
    }
}
