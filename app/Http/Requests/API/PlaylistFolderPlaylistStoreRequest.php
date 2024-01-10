<?php

namespace App\Http\Requests\API;

use App\Models\Playlist;
use App\Rules\AllPlaylistsBelongTo;
use Illuminate\Validation\Rule;

/**
 * @property-read array<int>|int $playlists
 */
class PlaylistFolderPlaylistStoreRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'playlists' => [
                'required',
                'array',
                new AllPlaylistsBelongTo($this->user()),
                Rule::exists(Playlist::class, 'id'),
            ],
        ];
    }
}
