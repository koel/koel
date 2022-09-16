<?php

namespace App\Http\Requests\V6\API;

use App\Http\Requests\API\Request;
use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $songs
 */
class AddSongsToPlaylistRequest extends Request
{
    /** @return array<mixed> */
    public function rules(): array
    {
        return [
            'songs' => 'required|array',
            'songs.*' => [Rule::exists(Song::class, 'id')],
        ];
    }
}
