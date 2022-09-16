<?php

namespace App\Http\Requests\V6\API;

use App\Http\Requests\API\Request;

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
