<?php

namespace App\Http\Requests;

/**
 * @property-read float|string $time
 * @property-read string $api_token
 * @property-read bool|string|null $progressive
 */
class SongPlayRequest extends Request
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'progressive' => ['nullable', 'boolean'],
            'time' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
