<?php

namespace App\Http\Requests\API;

/**
 * @property string $key
 */
class SetLastfmSessionKeyRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'key' => 'required',
        ];
    }
}
