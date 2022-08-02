<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $media_path
 */
class SettingRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'media_path' => 'string|required|path.valid',
        ];
    }
}
