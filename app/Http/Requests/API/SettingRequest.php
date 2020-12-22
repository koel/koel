<?php

namespace App\Http\Requests\API;

/**
 * @property string $media_path
 */
class SettingRequest extends Request
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'media_path' => 'string|required|path.valid',
        ];
    }
}
