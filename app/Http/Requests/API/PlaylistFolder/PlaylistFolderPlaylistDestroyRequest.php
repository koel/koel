<?php

namespace App\Http\Requests\API\PlaylistFolder;

use App\Http\Requests\API\Request;
use App\Models\Playlist;
use App\Rules\AllPlaylistsAreAccessibleBy;
use Illuminate\Validation\Rule;

/**
 * @property-read array<int>|int $playlists
 */
class PlaylistFolderPlaylistDestroyRequest extends Request
{
    /** @inheritdoc */
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
