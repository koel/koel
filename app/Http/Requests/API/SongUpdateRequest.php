<?php

namespace App\Http\Requests\API;

/**
 * @property array<string> $songs
 * @property array<mixed> $data
 */
class SongUpdateRequest extends Request
{
    public function authorize(): bool
    {
        return $this->user()->is_admin;
    }

    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'songs' => 'required|array',
        ];
    }
}
