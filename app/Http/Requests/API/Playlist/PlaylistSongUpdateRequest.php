<?php

namespace App\Http\Requests\API\Playlist;

use App\Http\Requests\API\Request;
use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property array<string> $songs
 */
class PlaylistSongUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => [Rule::present(), Rule::array()],
            'songs.*' => [Rule::exists(Song::class, 'id')],
        ];
    }
}
