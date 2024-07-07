<?php

namespace App\Http\Requests\API;

use App\Models\Playlist;
use App\Rules\AllPlaylistsAreAccessibleBy;
use Illuminate\Validation\Rule;

/**
 * @property-read array<int>|int $playlists
 */
class PlaylistFolderPlaylistDestroyRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'playlists' => [
                'required',
                'array',
                new AllPlaylistsAreAccessibleBy($this->user()),
                Rule::exists(Playlist::class, 'id'),
            ],
        ];
    }
}
