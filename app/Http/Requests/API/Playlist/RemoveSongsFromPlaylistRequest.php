<?php

namespace App\Http\Requests\API\Playlist;

use App\Http\Requests\API\Request;
use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $songs
 */
class RemoveSongsFromPlaylistRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => [Rule::required(), Rule::array(), Rule::exists(Song::class, 'id')],
        ];
    }
}
