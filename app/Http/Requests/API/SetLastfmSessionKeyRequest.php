<?php

namespace App\Http\Requests\API;

use Illuminate\Validation\Rule;

/**
 * @property string $key
 */
class SetLastfmSessionKeyRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'key' => [Rule::required()],
        ];
    }
}
