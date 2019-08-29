<?php

namespace App\Http\Requests\API;

/**
 * @property string[] $songs
 */
class PlaylistSyncRequest extends Request
{
    public function rules(): array
    {
        return [
            'songs' => 'present|array',
        ];
    }
}
