<?php

namespace App\Http\Requests\API;

/**
 * @property array songs
 * @property array data
 */
class SongUpdateRequest extends Request
{
    public function authorize(): bool
    {
        return $this->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'songs' => 'required|array',
        ];
    }
}
