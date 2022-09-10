<?php

namespace App\Http\Controllers\V6\Requests;

use App\Http\Requests\API\Request;
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
