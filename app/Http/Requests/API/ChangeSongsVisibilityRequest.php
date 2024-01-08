<?php

namespace App\Http\Requests\API;

/**
 * @property-read array<string> $songs
 */
class ChangeSongsVisibilityRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|exists:songs,id',
        ];
    }
}
