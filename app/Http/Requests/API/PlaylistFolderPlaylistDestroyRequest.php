<?php

namespace App\Http\Requests\API;

use App\Models\Playlist;
use App\Rules\AllPlaylistsBelongToUser;
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
            'playlists' => ['required', 'array', new AllPlaylistsBelongToUser($this->user())],
            'playlists.*' => [Rule::exists(Playlist::class, 'id')],
        ];
    }
}
