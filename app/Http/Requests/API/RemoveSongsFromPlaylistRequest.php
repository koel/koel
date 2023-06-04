<?php

namespace App\Http\Requests\API;

/**
 * @property-read array<string> $songs
 */
class RemoveSongsFromPlaylistRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|array',
            'songs.*' => 'exists:songs,id',
        ];
    }
}
