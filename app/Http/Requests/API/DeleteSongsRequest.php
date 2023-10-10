<?php

namespace App\Http\Requests\API;

/** @property-read array<string> $songs */
class DeleteSongsRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|array|exists:songs,id',
        ];
    }
}
