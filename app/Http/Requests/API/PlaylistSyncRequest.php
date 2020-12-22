<?php

namespace App\Http\Requests\API;

/**
 * @property array<string> $songs
 */
class PlaylistSyncRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'present|array',
        ];
    }
}
