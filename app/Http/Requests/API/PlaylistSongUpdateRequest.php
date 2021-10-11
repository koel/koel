<?php

namespace App\Http\Requests\API;

use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property array<string> $songs
 */
class PlaylistSongUpdateRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'present|array',
            'songs.*' => [Rule::exists(Song::class, 'id')],
        ];
    }
}
