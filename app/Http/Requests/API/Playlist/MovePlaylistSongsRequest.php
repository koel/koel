<?php

namespace App\Http\Requests\API\Playlist;

use App\Enums\Placement;
use App\Http\Requests\API\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * @property-read array<string> $songs
 * @property-read string $target
 * @property-read string $placement
 */
class MovePlaylistSongsRequest extends Request
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // We don't validate against the playlist_song table here, because songs might have been removed
            // from the playlist in the meantime.
            // Instead, we only validate that the songs exist.
            'songs' => 'required|array|exists:songs,id',
            'target' => 'required|exists:playlist_song,song_id',
            'placement' => ['required', new Enum(Placement::class)],
        ];
    }
}
